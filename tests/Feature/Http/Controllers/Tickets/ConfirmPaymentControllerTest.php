<?php

use App\Enums\PaymentReferenceStatus;
use App\Mail\TicketPurchaseMail;
use App\Models\PaymentReference;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Symfony\Component\Mime\Email;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

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
    ->assertOk()
    ->assertJsonStructure(['success', 'message', 'tickets']);

  $makeAssertion = $this->makeAssertion;
  $makeAssertion($this->paymentReference);
});

it('can confirm bank deposit ticket payment', function () {
  seed(RoleSeeder::class);
  $paymentReference = PaymentReference::factory()
    ->bankDeposit()
    ->ticketPayment()
    ->create();

  expect($paymentReference)
    ->status->not()
    ->toBe(PaymentReferenceStatus::Confirmed);
  postJson(route('api.tickets.bank-deposit.confirm'), [
    'reference' => $paymentReference->reference
  ])->assertUnauthorized();
  actingAs(
    User::factory()
      ->admin()
      ->create()
  )
    ->postJson(route('api.tickets.bank-deposit.confirm'), [
      'reference' => $paymentReference->reference
    ])
    // ->dump()
    ->assertOk();

  $makeAssertion = $this->makeAssertion;
  $makeAssertion($paymentReference);
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
    '',
    config('services.paystack.secret-key')
  );

  postJson(route('webhook.paystack'), $inputData)
    // ->dump()
    ->assertOk()
    ->assertJsonStructure(['success', 'message']);

  $makeAssertion = $this->makeAssertion;
  $makeAssertion($this->paymentReference);
});

// it('updates sent_at when ticket email is sent', function () {
//   Mail::fake();
//   Event::fake([MessageSent::class]);

//   $ticket = Ticket::factory()->create(['sent_at' => null]);

//   $mailable = new TicketPurchaseMail($ticket);

//   // Send the mail
//   Mail::to('test@example.com')->send($mailable);

//   // Assert event fired
//   Event::assertDispatched(MessageSent::class);
//   // Refresh ticket from database
//   $ticket->refresh();

//   expect($ticket->sent_at)->not->toBeNull();
//   expect($ticket->sent_->isToday())->toBeTrue();
// });
