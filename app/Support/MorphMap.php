<?php
namespace App\Support;

use App\Models;

class MorphMap
{
  static function key($value): string|null
  {
    return array_search($value, self::MAP);
  }

  static function keys(array $values): array
  {
    $keys = [];
    foreach ($values as $key => $value) {
      if ($searchKey = array_search($value, self::MAP)) {
        $keys[] = $searchKey;
      }
    }
    return $keys;
  }

  function value($key): string|null
  {
    return self::MAP[$key] ?? null;
  }

  const MAP = [
    'user' => Models\User::class,
    'event' => Models\Event::class,
    'event-category' => Models\EventCategory::class,
    'event-image' => Models\EventImage::class,
    'event-package' => Models\EventPackage::class,
    'event-season' => Models\EventSeason::class,
    'payment' => Models\Payment::class,
    'payment-reference' => Models\PaymentReference::class,
    'seat' => Models\Seat::class,
    'seat-section' => Models\SeatSection::class,
    'ticket' => Models\Ticket::class,
    'ticket-payment' => Models\TicketPayment::class
  ];
}
