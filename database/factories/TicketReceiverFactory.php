<?php

namespace Database\Factories;

use App\Models\TicketPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketReceiverFactory extends Factory
{
  public function definition(): array
  {
    return [
      'ticket_payment_id' => TicketPayment::factory(),
      'name' => fake()->name(),
      'phone' => fake()->phoneNumber(),
      'email' => fake()->email(),
      'meta' => [
        'age' => fake()->numberBetween(18, 60),
        'notes' => fake()->sentence()
      ]
    ];
  }
}
