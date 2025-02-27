<?php

namespace App\Http\Controllers\Api\Seats;

use App\Actions\GetAvailableSeats;
use App\Enums\SeatStatus;
use App\Http\Controllers\Controller;
use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\SeatSection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

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
    $query = GetAvailableSeats::run($eventPackage);
    return $this->apiRes(paginateFromRequest($query));
  }

  function store(Request $request, SeatSection $seatSection)
  {
    $data = $request->validate([
      'seat_no' => ['required', 'string'],
      'description' => ['nullable', 'string'],
      'status' => ['sometimes', new Enum(SeatStatus::class)]
    ]);

    $seat = $seatSection
      ->seats()
      ->firstOrCreate(['seat_no' => $data['seat_no']], $data);
    return $this->apiRes($seat);
  }
}
