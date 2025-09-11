<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReceiver extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'ticket_payment_id' => 'integer',
    'meta' => AsArrayObject::class
  ];

  static function prepareData($data): array
  {
    $collect = collect($data);
    return [
      ...$collect->only(['name', 'phone', 'email'])->toArray(),
      'meta' => $collect->except(['name', 'phone', 'email'])->toArray()
    ];
  }

  function ticketPayment()
  {
    return $this->belongsTo(TicketPayment::class);
  }
}
