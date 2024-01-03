<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSeason extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'date_from' => 'datetime',
    'date_to' => 'datetime'
  ];

  function scopeUpcomingSeason($query)
  {
    return $query->where('date_from', '>', now());
  }

  function eventCategory()
  {
    return $this->belongsTo(EventCategory::class);
  }

  function events()
  {
    return $this->hasMany(Event::class);
  }
}
