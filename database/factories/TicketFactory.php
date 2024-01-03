<?php

namespace Database\Factories;

use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\TicketPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class TicketFactory extends Factory
{
  public function definition(): array
  {
    return [
      'ticket_payment_id' => TicketPayment::factory()->paymentReference(),
      'event_package_id' => EventPackage::factory(),
      'seat_id' => Seat::factory(),
      'user_id' => User::factory(),
      'reference' => Str::orderedUuid(),
      'qr_code' => fake()->imageUrl()
    ];
  }
}
