<?php

namespace App\Http\Controllers\Api\Seats;

use App\Actions\GetAvailableSeats;
use App\Http\Controllers\Controller;
use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\SeatSection;
use Illuminate\Http\Request;

/**
 * @group Seats
 */
class SeatController extends Controller
{
  public function index(Request $request, SeatSection $seatSection = null)
  {
    $seatSections = Seat::query()->seatSectionId($seatSection?->id);
    return $this->apiRes(paginateFromRequest($seatSections));
  }

  public function available(Request $request, EventPackage $eventPackage)
  {
    // $query = Seat::query()
    //   ->whereDoesntHave(
    //     'tickets',
    //     fn($q) => $q->where('event_package_id', $eventPackage->id)
    //   )
    //   ->where('seats.seat_section_id', $eventPackage->seat_section_id);
    $query = GetAvailableSeats::run($eventPackage);
    return $this->apiRes(paginateFromRequest($query));
  }
}
