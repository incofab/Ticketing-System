<?php

namespace App\Models;

use App\Enums\ExtraUserDataType;
use App\Enums\PaymentMerchantType;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @property array{
 *   extra_user_data: array {
 *    name: string,
 *    type: string,
 *    is_required: bool
 *   }[]
 * }|null $meta
 */
class Event extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  protected $appends = ['expired'];
  protected $casts = [
    'user_id' => 'integer',
    'event_season_id' => 'integer',
    'start_time' => 'datetime',
    'end_time' => 'datetime',
    'payment_merchants' => 'array',
    'meta' => AsArrayObject::class
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
      'logo' => ['nullable', 'image'],
      'payment_merchants' => ['nullable', 'array', 'min:0'],
      'payment_merchants.*' => [
        'required',
        new Enum(PaymentMerchantType::class)
      ],
      'meta' => ['nullable', 'array'],
      'meta.extra_user_data' => ['nullable', 'array', 'min:1'],
      'meta.extra_user_data.*.name' => ['required', 'string', 'max:255'],
      'meta.extra_user_data.*.type' => [
        'required',
        new Enum(ExtraUserDataType::class)
      ],
      'meta.extra_user_data.*.is_required' => ['required', 'boolean']
    ];
  }

  function scopeUpcomingEvents($query)
  {
    return $query->where('events.start_time', '>', now());
  }

  function scopePastEvents($query)
  {
    return $query->where('events.start_time', '<', now());
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

  function coupons()
  {
    return $this->hasMany(Coupon::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
