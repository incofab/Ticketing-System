<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventImage extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'event_id' => 'integer',
    'user_id' => 'integer'
  ];

  function event()
  {
    return $this->belongsTo(Event::class);
  }
}
