<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'status' => SeatStatus::class
  ];

  function seatSection()
  {
    return $this->belongsTo(SeatSection::class);
  }

  function tickets()
  {
    return $this->hasMany(Ticket::class);
  }
}
