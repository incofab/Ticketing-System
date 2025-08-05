<?php

namespace Database\Factories;

use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketPaymentFactory extends Factory
{
  public function definition(): array
  {
    return [
      'event_package_id' => EventPackage::factory(),
      'quantity' => fake()->randomDigit(2),
      'user_id' => User::factory(),
      'name' => fake()->name(),
      'phone' => fake()->phoneNumber(),
      'email' => fake()->email(),
      'receivers' => collect(range(1, 1))->map(fn($item) => fake()->email()),
      'referral_code' => fake()
        ->optional()
        ->word(),
      'amount' => fake()->randomFloat(2, 100, 1000)
    ];
  }

  function paymentReference()
  {
    return $this->afterCreating(function (TicketPayment $ticketPayment) {
      PaymentReference::factory()
        ->ticketPayment($ticketPayment)
        ->confirmed()
        ->create();
    });
  }

  function receiver($count = 1)
  {
    return $this->state(
      fn($attr) => [
        'receivers' => collect(range(1, $count))->map(
          fn($item) => fake()->email()
        )
      ]
    );
  }
}
