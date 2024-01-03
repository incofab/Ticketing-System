<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeatSectionFactory extends Factory
{
  public function definition(): array
  {
    return [
      'title' => fake()->sentence(),
      'description' => fake()->sentence(10),
      'features' => fake()->sentence(8),
      'capacity' => fake()->randomNumber(3)
    ];
  }
}
