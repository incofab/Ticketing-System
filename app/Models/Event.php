<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Event extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  protected $appends = ['expired'];
  protected $casts = [
    'user_id' => 'integer',
    'start_time' => 'datetime',
    'end_time' => 'datetime'
  ];

  static function createRule($eventSeasonId, Event|null $event = null)
  {
    return [
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('events', 'title')
          ->where('event_season_id', $eventSeasonId)
          ->when($event, fn($q) => $q->ignore($event->id, 'id'))
      ],
      'description' => ['nullable', 'string'],
      'start_time' => ['sometimes', 'required', 'date'],
      'end_time' => ['sometimes', 'date', 'after:start_time'],
      'home_team' => ['nullable', 'string', 'max:255'],
      'away_team' => ['nullable', 'string', 'max:255'],
      'venue' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:255'],
      'email' => ['nullable', 'email', 'max:255'],
      'website' => ['nullable', 'string', 'max:255'],
      'facebook' => ['nullable', 'string', 'max:255'],
      'twitter' => ['nullable', 'string', 'max:255'],
      'instagram' => ['nullable', 'string', 'max:255'],
      'youtube' => ['nullable', 'string', 'max:255'],
      'tiktok' => ['nullable', 'string', 'max:255'],
      'linkedin' => ['nullable', 'string', 'max:255'],
      'logo' => ['nullable', 'image']
    ];
  }

  function scopeUpcomingEvents($query)
  {
    return $query->where('start_time', '>', now());
  }

  protected function expired(): Attribute
  {
    return Attribute::make(get: fn() => $this->isExpired());
  }

  function isExpired()
  {
    return now()->greaterThan($this->end_time ?? $this->start_time);
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

  function eventAttendees()
  {
    return $this->hasMany(EventAttendee::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
