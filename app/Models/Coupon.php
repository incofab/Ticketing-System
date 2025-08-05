<?php

namespace App\Models;

use App\Enums\CouponDiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class Coupon extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'event_id' => 'integer',
    'expires_at' => 'datetime',
    'discount_value' => 'float:2',
    'min_purchase' => 'float:2',
    'discount_type' => CouponDiscountType::class,
    'usage_limit' => 'integer',
    'usage_count' => 'integer'
  ];

  static function createRule(?self $model = null)
  {
    return [
      'code' => [
        'required',
        'string',
        Rule::unique('coupons', 'code')->when(
          $model,
          fn($q) => $q->ignore($model->id, 'id')
        )
      ],
      'discount_type' => ['required', new Enum(CouponDiscountType::class)],
      'discount_value' => ['required', 'numeric', 'min:0'],
      'min_purchase' => ['nullable', 'numeric', 'min:0'],
      'expires_at' => ['nullable', 'date'],
      'usage_limit' => ['nullable', 'integer', 'min:0'],
      'usage_count' => ['integer', 'min:0'],
      'event_id' => ['required', 'integer', 'exists:events,id'],
      'event_package_ids' => ['sometimes', 'array'],
      'event_package_ids.*' => ['integer', 'exists:event_packages,id']
    ];
  }

  /**
   * Check if the coupon is valid.
   *
   * @param float $purchaseAmount
   * @return bool|string True if valid, or an error message string if not.
   */
  public function isValid(float $purchaseAmount = 0): bool|string
  {
    if ($this->expires_at && $this->expires_at->isPast()) {
      return 'Coupon has expired.';
    }

    if (
      !empty($this->usage_limit) &&
      $this->usage_count >= $this->usage_limit
    ) {
      return 'Coupon usage limit has been reached.';
    }

    if (!empty($this->min_purchase) && $purchaseAmount < $this->min_purchase) {
      return 'Minimum purchase amount not met.';
    }

    return true;
  }

  /**
   * Calculate the discount for a given amount.
   *
   * @param float $amount
   * @return float
   */
  public function getDiscount(float $amount): float
  {
    if ($this->isValid($amount) !== true) {
      info($this->isValid($amount));
      return 0;
    }
    if ($this->discount_type === CouponDiscountType::Percentage) {
      $discount = ($amount * $this->discount_value) / 100;
    } elseif ($this->discount_type === CouponDiscountType::Fixed) {
      $discount = $this->discount_value;
    } else {
      $discount = 0;
    }

    // Ensure discount doesn't exceed the total amount
    return min($discount, $amount);
  }

  function event()
  {
    $this->belongsTo(Event::class);
  }

  function eventPackages()
  {
    return $this->belongsToMany(EventPackage::class);
  }

  function couponEventPackages()
  {
    return $this->hasMany(CouponEventPackage::class);
  }

  function ticketPayments()
  {
    return $this->hasMany(TicketPayment::class);
  }
}
