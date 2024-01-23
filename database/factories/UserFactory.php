<?php

namespace Database\Factories;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'other_names' => fake()->word(),
      'email' => $this->faker->unique()->safeEmail,
      'phone' => $this->faker->unique()->phoneNumber,
      'email_verified_at' => now(),
      'password' =>
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
      'remember_token' => Str::random(10)
    ];
  }

  function admin()
  {
    return $this->role(RoleType::Admin);
  }

  function role($role = RoleType::Admin)
  {
    return $this->afterCreating(function (User $user) use ($role) {
      $user->syncRoles($role);
    });
  }
}
