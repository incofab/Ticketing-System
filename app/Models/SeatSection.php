<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatSection extends Model
{
  use HasFactory;

  protected $guarded = [];

  function seats()
  {
    return $this->hasMany(Seat::class);
  }
}
