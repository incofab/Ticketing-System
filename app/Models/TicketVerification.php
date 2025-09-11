<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketVerification extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'ticket_id' => 'integer',
    'user_id' => 'integer'
  ];

  function isVerificationStillValid($deviceNo, $reference)
  {
    if ($this->device_no !== $deviceNo) {
      return false;
    }
    // if ($this->reference !== $reference) {
    //   return false;
    // }
    $allowanceInSeconds = 3;
    return $this->created_at
      ->addSeconds($allowanceInSeconds)
      ->greaterThanOrEqualTo(now());
  }

  function ticket()
  {
    return $this->belongsTo(Ticket::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
