<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\EventPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CouponEventPackage>
 */
class CouponEventPackageFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'coupon_id' => Coupon::factory(),
      'event_package_id' => EventPackage::factory()
    ];
  }
}
