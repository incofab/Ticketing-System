<?php

namespace Database\Factories;

use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventSeasonFactory extends Factory
{
  public function definition(): array
  {
    return [
      'event_category_id' => EventCategory::factory(),
      'title' => fake()->sentence(),
      'description' => fake()->sentence(10),
      'date_from' => now()->addMonth(),
      'date_to' => now()->addMonths(4)
    ];
  }
}
