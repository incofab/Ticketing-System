<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketVerificationFactory extends Factory
{
  public function definition(): array
  {
    return [
      'ticket_id' => Ticket::factory(),
      'user_id' => User::factory(),
      'reference' => Str::uuid(),
      'device_no' => fake()
        ->unique()
        ->word()
    ];
  }
}
