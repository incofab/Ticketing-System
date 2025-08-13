<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'ticket_payment_id' => 'integer',
    'event_package_id' => 'integer',
    'seat_id' => 'integer',
    'user_id' => 'integer',
    'qr_code' => 'string',
    'sent_at' => 'datetime'
  ];

  function ticketPayment()
  {
    return $this->belongsTo(TicketPayment::class);
  }

  function eventPackage()
  {
    return $this->belongsTo(EventPackage::class);
  }

  function seat()
  {
    return $this->belongsTo(Seat::class);
  }

  function eventAttendee()
  {
    return $this->hasOne(EventAttendee::class);
  }

  function ticketVerification()
  {
    return $this->hasOne(TicketVerification::class);
  }
}
