<?php

use App\Enums\PaymentReferenceStatus;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use App\Models\User;
use App\Support\Payment\Processor\TicketPaymentProcessor;

beforeEach(function () {
  $this->user = User::factory()->create();
  $this->event = Event::factory()->create();
  $this->eventPackage = EventPackage::factory()->create([
    'event_id' => $this->event->id
  ]);
  $this->ticketPayment = TicketPayment::factory()->create([
    'user_id' => $this->user->id,
    'event_package_id' => $this->eventPackage->id,
    'quantity' => 2
  ]);

  $this->paymentReference = PaymentReference::factory()->create([
    'paymentable_id' => $this->ticketPayment->id,
    'paymentable_type' => TicketPayment::class,
    'amount' => 2000,
    'status' => PaymentReferenceStatus::Pending
  ]);
  $this->processor = TicketPaymentProcessor::make($this->paymentReference);

  $this->fakeHttp = function ($status, $amount = 100) {
    $url = "https://api.paystack.co/transaction/verify/{$this->paymentReference->reference}";
    Http::fake([
      $url => Http::response(
        [
          'status' => $status,
          'data' => [
            'status' => $status,
            'amount' => ceil($this->paymentReference->amount) * $amount
          ]
        ],
        200
      )
    ]);
  };
});

it('should successfully handle a pending payment', function () {
  $fakeHttp = $this->fakeHttp;
  $fakeHttp('success');
  $processor = TicketPaymentProcessor::make($this->paymentReference);

  [$result] = $processor->handleCallback();
  expect($result->isSuccessful())->toBeTrue();
  $this->paymentReference->refresh();

  expect($this->paymentReference->status)->toBe(
    PaymentReferenceStatus::Confirmed
  );
});

it('should return success if the payment is already confirmed', function () {
  $this->paymentReference->update([
    'status' => PaymentReferenceStatus::Confirmed
  ]);

  [$result] = $this->processor->handleCallback();

  expect($result->isSuccessful())->toBeTrue();
  expect($result->message)->toBe('Payment already completed');
  expect($this->paymentReference->status)->toBe(
    PaymentReferenceStatus::Confirmed
  );
});

it('should return success if the payment is already canceled', function () {
  $this->paymentReference->update([
    'status' => PaymentReferenceStatus::Cancelled
  ]);

  [$result] = $this->processor->handleCallback();

  expect($result->isSuccessful())->toBeTrue();
  expect($result->message)->toBe('Payment already completed');
  expect($this->paymentReference->status)->toBe(
    PaymentReferenceStatus::Cancelled
  );
});

it(
  'should cancel the payment if verification fails with a failure status',
  function () {
    $this->paymentReference = PaymentReference::factory()
      ->backDateCreationDate(30)
      ->create([
        'paymentable_id' => $this->ticketPayment->id,
        'paymentable_type' => TicketPayment::class,
        'amount' => 2000,
        'status' => PaymentReferenceStatus::Pending
      ]);
    $fakeHttp = $this->fakeHttp;
    $fakeHttp('failed');
    $processor = TicketPaymentProcessor::make($this->paymentReference);
    [$result] = $processor->handleCallback();

    expect($result->isSuccessful())->toBeFalse();

    $this->paymentReference->refresh();
    expect($this->paymentReference->status->value)->toBe(
      PaymentReferenceStatus::Cancelled->value
    );
  }
);

it(
  'should handle payment verification failing with non failure status',
  function () {
    $fakeHttp = $this->fakeHttp;
    $fakeHttp('pending');
    $processor = TicketPaymentProcessor::make($this->paymentReference);

    [$result] = $processor->handleCallback();

    expect($result->isSuccessful())->toBeFalse();
    $this->paymentReference->refresh();
    expect($this->paymentReference->status)->toBe(
      PaymentReferenceStatus::Pending
    );
  }
);

it('should handle insufficient payment', function () {
  $fakeHttp = $this->fakeHttp;
  $fakeHttp('success', 0.5);
  $processor = TicketPaymentProcessor::make($this->paymentReference);
  [$result] = $processor->handleCallback();

  expect($result->isSuccessful())->toBeFalse();
  $this->paymentReference->refresh();
  expect($this->paymentReference->status)->toBe(
    PaymentReferenceStatus::Pending
  );
});
