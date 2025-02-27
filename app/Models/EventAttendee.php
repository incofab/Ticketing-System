<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'event_id' => 'integer',
    'ticket_id' => 'integer'
  ];

  function scopeEventId($query, $eventId)
  {
    return $query->when(
      $eventId,
      fn($q, $value) => $q->where('event_id', $value)
    );
  }

  function scopeTicketId($query, $ticketId)
  {
    return $query->when(
      $ticketId,
      fn($q, $value) => $q->where('ticket_id', $value)
    );
  }

  function event()
  {
    return $this->belongsTo(Event::class);
  }

  function ticket()
  {
    return $this->belongsTo(Ticket::class);
  }
}
