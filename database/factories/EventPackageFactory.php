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
      'event_id' => Event::factory(),
      'seat_section_id' => SeatSection::factory(),
      'price' => fake()->randomFloat(1, 1000, 10000)
    ];
  }
}
