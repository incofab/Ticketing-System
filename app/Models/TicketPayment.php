<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPayment extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'event_package_id' => 'integer',
    'user_id' => 'integer',
    'quantity' => 'integer'
  ];

  function markProcessing(bool $isProcessing)
  {
    $this->fill(['processing' => $isProcessing])->save();
  }

  function eventPackage()
  {
    return $this->belongsTo(EventPackage::class);
  }

  function tickets()
  {
    return $this->hasMany(Ticket::class);
  }

  function paymentReferences()
  {
    return $this->morphMany(PaymentReference::class, 'paymentable');
  }
}
