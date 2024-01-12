<?php

use App\Enums\PaymentReferenceStatus;
use App\Models\PaymentReference;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->create();

  $url = "https://api.paystack.co/transaction/verify/{$this->paymentReference->reference}";
  Http::fake([
    $url => Http::response(
      [
        'status' => true,
        'data' => [
          'status' => 'success',
          'amount' => ceil($this->paymentReference->amount) * 100
        ]
      ],
      200
    )
  ]);

  $this->makeAssertion = function (PaymentReference $paymentReference) {
    $ticketPayment = $paymentReference->paymentable;
    $eventPackage = $ticketPayment->eventPackage;

    expect($paymentReference->fresh())->status->toBe(
      PaymentReferenceStatus::Confirmed
    );
    expect($eventPackage->fresh()->quantity_sold)->toBe(
      $ticketPayment->quantity
    );
  };
});

it('can confirm paystack ticket payment', function () {
  expect($this->paymentReference)
    ->status->not()
    ->toBe(PaymentReferenceStatus::Confirmed);
  postJson(route('api.tickets.confirm-payment'), [
    'reference' => $this->paymentReference->reference
  ])
    // ->dump()
    ->assertOk();

  $makeAssertion = $this->makeAssertion;
  $makeAssertion($this->paymentReference);

  /*
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->create();
  $ticketPayment = $paymentReference->paymentable;
  $eventPackage = $ticketPayment->eventPackage;

  $url = "https://api.paystack.co/transaction/verify/{$paymentReference->reference}";
  Http::fake([
    $url => Http::response(
      [
        'status' => true,
        'data' => [
          'status' => 'success',
          'amount' => ceil($paymentReference->amount) * 100
        ]
      ],
      200
    )
  ]);

  expect($paymentReference)
    ->status->not()
    ->toBe(PaymentReferenceStatus::Confirmed);
  postJson(route('api.tickets.confirm-payment'), [
    'reference' => $paymentReference->reference
  ])
    // ->dump()
    ->assertOk();
  expect($paymentReference->fresh())->status->toBe(
    PaymentReferenceStatus::Confirmed
  );
  expect($eventPackage->fresh()->quantity_sold)->toBe($ticketPayment->quantity);
  */
});

it('handles Paystack webhook successfully', function () {
  // Mock the necessary data
  $inputData = [
    'event' => 'charge.success',
    'data' => [
      'status' => 'success',
      'customer' => [
        'email' => 'test@example.com',
        'amount' => 5000 // Replace with your desired amount
      ],
      'reference' => $this->paymentReference->reference
    ]
  ];

  $_SERVER['REQUEST_METHOD'] = 'POST';
  $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] = hash_hmac(
    'sha512',
    json_encode($inputData),
    config('services.paystack.secret-key')
  );

  postJson(route('webhook.paystack'), $inputData)
    // ->dump()
    ->assertOk()
    ->assertJsonStructure(['success', 'message']);

  $makeAssertion = $this->makeAssertion;
  $makeAssertion($this->paymentReference);
});
