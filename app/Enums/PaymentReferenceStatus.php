<?php

namespace App\Enums;

enum PaymentReferenceStatus: string
{
  case Pending = 'pending';
  case Cancelled = 'cancelled';
  case Confirmed = 'confirmed';
}
