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
      'email' => fake()->email()
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
}
