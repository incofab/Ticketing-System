<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EventCategoryFactory extends Factory
{
  public function definition(): array
  {
    return [
      'title' => fake()
        ->unique()
        ->sentence(),
      'description' => fake()->sentence(10)
    ];
  }
}
