<?php

namespace App\Enums;

enum CouponDiscountType: string
{
  case Fixed = 'fixed';
  case Percentage = 'percentage';
}
