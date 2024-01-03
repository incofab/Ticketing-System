<?php

namespace App\Models;

use App\Enums\PaymentMerchantType;
use App\Enums\PaymentReferenceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class PaymentReference extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'paymentable_id' => 'integer',
    'user_id' => 'integer',
    'merchant' => PaymentMerchantType::class,
    'status' => PaymentReferenceStatus::class
  ];

  static function generateReference()
  {
    $ref = self::getCode();
    while (self::where('reference', $ref)->exists()) {
      $ref = self::getCode();
    }
    return $ref;
  }

  private static function getCode()
  {
    return substr(str_replace('-', '', Str::uuid()), 0, 15);
  }

  // TicketPayment
  function paymentable()
  {
    return $this->morphTo();
  }

  function payments()
  {
    return $this->hasMany(Payment::class);
  }
}
