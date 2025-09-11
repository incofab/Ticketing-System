<?php

namespace Database\Factories;

use App\Enums\ExtraUserDataType;
use App\Models\EventSeason;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
  public function definition(): array
  {
    return [
      'user_id' => User::factory(),
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
        ->word(),
      'facebook' => fake()->url(),
      'twitter' => fake()->url(),
      'tiktok' => fake()->url(),
      'linkedin' => fake()->url(),
      'instagram' => fake()->url(),
      'youtube' => fake()->url(),
      'meta' => [
        'extra_user_data' => [
          [
            'name' => 'Age',
            'type' => ExtraUserDataType::Integer->value,
            'is_required' => true
          ],
          [
            'name' => 'Notes',
            'type' => ExtraUserDataType::LongText->value,
            'is_required' => false
          ]
        ]
      ]
    ];
  }

  function expired()
  {
    return $this->state(
      fn($attr) => [
        'start_time' => now()->subHours(3),
        'end_time' => now()->subHours(1)
      ]
    );
  }

  function user(User $user)
  {
    return $this->state(fn($attr) => ['user_id' => $user]);
  }
}
