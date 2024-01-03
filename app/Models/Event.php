<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'start_time' => 'datetime',
    'end_time' => 'datetime'
  ];

  function scopeUpcomingEvents($query)
  {
    return $query->where('start_time', '>', now());
  }

  function eventSeason()
  {
    return $this->belongsTo(EventSeason::class);
  }

  function eventPackages()
  {
    return $this->hasMany(EventPackage::class);
  }

  function eventImages()
  {
    return $this->hasMany(EventImage::class);
  }
}
