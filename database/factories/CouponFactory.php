<?php

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'event_id' => Event::factory(),
      'code' => strtoupper(Str::random(8)),
      'discount_type' => $this->faker->randomElement(
        CouponDiscountType::cases()
      )->value,
      'discount_value' => $this->faker->numberBetween(5, 50),
      'min_purchase' => $this->faker->randomElement([0, 1000, 5000]),
      'usage_limit' => $this->faker->randomElement([null, 10, 100]),
      'usage_count' => 0,
      'expires_at' => $this->faker->randomElement([null, now()->addMonth()])
    ];
  }

  function event(Event $event)
  {
    return $this->state(fn(array $attr) => ['event_id' => $event->id]);
  }

  function fixed()
  {
    return $this->state(function (array $attributes) {
      return [
        'discount_type' => CouponDiscountType::Fixed->value
      ];
    });
  }
  function percentage()
  {
    return $this->state(function (array $attributes) {
      return [
        'discount_type' => CouponDiscountType::Percentage->value
      ];
    });
  }
}
