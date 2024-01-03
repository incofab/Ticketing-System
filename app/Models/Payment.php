<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'payment_reference_id' => 'integer',
    'user_id' => 'integer'
  ];

  function paymentReference()
  {
    return $this->belongsTo(PaymentReference::class);
  }
}
