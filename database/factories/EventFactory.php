<?php

namespace Database\Factories;

use App\Models\EventSeason;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
  public function definition(): array
  {
    return [
      'event_season_id' => EventSeason::factory(),
      'title' => fake()->sentence(),
      'description' => fake()->sentence(10),
      'start_time' => now()->addMonths(2),
      'end_time' => now()->addMonths(3),
      'home_team' => fake()
        ->unique()
        ->word(),
      'away_team' => fake()
        ->unique()
        ->word()
    ];
  }
}
