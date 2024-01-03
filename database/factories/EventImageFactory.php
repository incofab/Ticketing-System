<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class EventImageFactory extends Factory
{
  public function definition(): array
  {
    return [
      'event_id' => Event::factory(),
      'reference' => Str::orderedUuid(),
      'image' => fake()->imageUrl()
    ];
  }
}
