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
    'quantity' => 'integer',
    'coupon_id' => 'integer',
    'amount' => 'float',
    'original_amount' => 'float',
    'discount_amount' => 'float',
    'receivers' => 'array',
    'processing' => 'boolean'
  ];

  function saveReceivers($receivers)
  {
    $this->ticketReceivers()->delete();
    foreach ($receivers as $data) {
      $this->ticketReceivers()->create(TicketReceiver::prepareData($data));
    }
  }

  function markProcessing(bool $isProcessing)
  {
    $this->fill(['processing' => $isProcessing])->save();
  }

  /** @deprecated No longer used */
  function getReceiverEmail($index = 0)
  {
    return $this->ticketReceivers[$index]?->email ?? $this->email;
  }

  function getReceiverByIndex($index = 0): TicketReceiver|null
  {
    return $this->ticketReceivers[$index] ?? $this->ticketReceivers->first();
  }

  function eventPackage()
  {
    return $this->belongsTo(EventPackage::class);
  }

  function tickets()
  {
    return $this->hasMany(Ticket::class);
  }

  function paymentReference()
  {
    return $this->morphOne(PaymentReference::class, 'paymentable');
  }
  /** @deprecated */
  function paymentReferences()
  {
    return $this->morphMany(PaymentReference::class, 'paymentable');
  }

  function coupon()
  {
    return $this->belongsTo(Coupon::class);
  }

  function ticketReceivers()
  {
    return $this->hasMany(TicketReceiver::class);
  }
}
