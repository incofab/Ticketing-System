<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\SeatSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventPackageFactory extends Factory
{
  public function definition(): array
  {
    return [
      'title' => fake()
        ->unique()
        ->word(),
      'event_id' => Event::factory(),
      'seat_section_id' => SeatSection::factory(),
      'price' => fake()->randomFloat(1, 1000, 10000),
      'capacity' => fake()->randomNumber(2) + 5,
      'entry_gate' => fake()->word(),
      'quantity_sold' => 0
    ];
  }

  function event(Event $event)
  {
    return $this->state(fn($attr) => ['event_id' => $event]);
  }
}
