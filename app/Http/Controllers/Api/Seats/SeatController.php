<?php

namespace App\Http\Controllers\Api\Seats;

use App\Http\Controllers\Controller;
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
}
