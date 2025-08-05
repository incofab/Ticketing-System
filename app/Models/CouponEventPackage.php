<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponEventPackage extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $table = 'coupon_event_package'; // No pluralization because it's a pivot table

  protected $casts = [
    'coupon_id' => 'integer',
    'event_package_id' => 'integer'
  ];

  function coupon()
  {
    return $this->belongsTo(Coupon::class);
  }

  function eventPackage()
  {
    return $this->belongsTo(EventPackage::class);
  }
}
