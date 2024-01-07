<?php

namespace Database\Factories;

use App\Models\SeatSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
  public function definition(): array
  {
    return [
      'seat_section_id' => SeatSection::factory(),
      'seat_no' => fake()->numerify('#####'),
      'description' => fake()->sentence(10),
      'features' => fake()->sentence(10)
    ];
  }

  function seatSection(SeatSection $seatSection)
  {
    return $this->state(fn($attr) => ['seat_section_id' => $seatSection]);
  }
}
