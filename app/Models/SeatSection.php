<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeatSection extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];

  function seats()
  {
    return $this->hasMany(Seat::class);
  }

  function eventPackages()
  {
    return $this->hasMany(EventPackage::class);
  }
}
