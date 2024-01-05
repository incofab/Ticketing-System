<?php

namespace Database\Factories;

use App\Enums\PaymentReferenceStatus;
use App\Models\Payment;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class PaymentReferenceFactory extends Factory
{
  public function definition(): array
  {
    return [
      'reference' => Str::orderedUuid(),
      'amount' => fake()->randomFloat(2, 10000, 100000),
      'status' => PaymentReferenceStatus::Pending
    ];
  }

  function ticketPayment(TicketPayment $ticketPayment = null)
  {
    $ticketPayment = $ticketPayment ?? TicketPayment::factory()->create();
    return $this->state(
      fn($attr) => [
        'paymentable_type' => $ticketPayment->getMorphClass(),
        'paymentable_id' => $ticketPayment->id,
        'user_id' => $ticketPayment->user_id
      ]
    );
  }

  function confirmed()
  {
    return $this->afterCreating(function (PaymentReference $paymentReference) {
      $paymentReference
        ->fill(['status' => PaymentReferenceStatus::Confirmed])
        ->save();
      Payment::factory()
        ->paymentReference($paymentReference)
        ->create();
    });
  }
}
