<?php
namespace App\Actions;

use App\Models\EventPackage;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Builder;

class GetAvailableSeats
{
  static function run(EventPackage $eventPackage): Builder
  {
    return Seat::query()
      ->whereDoesntHave(
        'tickets',
        fn($q) => $q->where('event_package_id', $eventPackage->id)
      )
      ->where('seats.seat_section_id', $eventPackage->seat_section_id)
      ->oldest('seats.seat_no');
  }
}
