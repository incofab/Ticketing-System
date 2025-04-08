<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPackage;
use App\Models\SeatSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatSectionFactory extends Factory
{
  public function definition(): array
  {
    return [
      'title' => fake()->sentence(),
      'description' => fake()->sentence(10),
      'features' => fake()->sentence(8),
      'capacity' => fake()->randomNumber(3) + 100
    ];
  }

  function eventPackages(Event|null $event = null, $count = 2)
  {
    return $this->afterCreating(function (SeatSection $model) use (
      $event,
      $count
    ) {
      EventPackage::factory($count)
        ->when($event, fn($q) => $q->event($event))
        ->for($model)
        ->create(['quantity_sold' => fake()->randomNumber(1)]);
    });
  }
}
