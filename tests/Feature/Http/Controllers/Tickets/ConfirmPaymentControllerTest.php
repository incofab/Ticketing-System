<?php

use App\Enums\PaymentReferenceStatus;
use App\Models\PaymentReference;

use function Pest\Laravel\postJson;

beforeEach(function () {});

it('can confirm paystack ticket payment', function () {
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->create();

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
});
