<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventPackage extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'event_id' => 'integer',
    'seat_section_id' => 'integer',
    'quantity_sold' => 'integer',
    'price' => 'float'
  ];

  function scopeEventId($query, $eventId)
  {
    return $query->when(
      $eventId,
      fn($q, $value) => $q->where('event_id', $value)
    );
  }

  function scopeSeatSectionId($query, $seatSectionId)
  {
    return $query->when(
      $seatSectionId,
      fn($q, $value) => $q->where('seat_section_id', $value)
    );
  }

  function seatSection()
  {
    return $this->belongsTo(SeatSection::class);
  }

  function event()
  {
    return $this->belongsTo(Event::class);
  }

  function ticketPayments()
  {
    return $this->belongsTo(TicketPayment::class);
  }
}
