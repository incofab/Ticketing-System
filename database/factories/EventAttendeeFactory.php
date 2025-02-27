<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventAttendeeFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'ticket_id' => Ticket::factory(),
      'event_id' => Event::factory(),
      'name' => fake()->unique()->name,
      'email' => fake()->unique()->safeEmail,
      'phone' => fake()
        ->unique()
        ->numerify('###########'),
      'address' => fake()->unique()->address
    ];
  }

  function ticket(Ticket $ticket)
  {
    return $this->state(
      fn($attr) => [
        'ticket_id' => $ticket->id,
        'event_id' => $ticket->eventPackage->event_id
      ]
    );
  }
}
