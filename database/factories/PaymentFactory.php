<?php

namespace Database\Factories;

use App\Models\PaymentReference;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
  public function definition(): array
  {
    return [
      'payment_reference_id' => PaymentReference::factory()->ticketPayment(),
      'amount' => fake()->randomFloat(2, 10000, 100000)
    ];
  }

  function paymentReference(PaymentReference $paymentReference)
  {
    return $this->state(
      fn($attr) => [
        'payment_reference_id' => $paymentReference->id,
        'user_id' => $paymentReference->user_id,
        'amount' => $paymentReference->amount
      ]
    );
  }
}
