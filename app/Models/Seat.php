<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seat extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  protected $casts = [
    'status' => SeatStatus::class
  ];

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

  function tickets()
  {
    return $this->hasMany(Ticket::class);
  }
}
