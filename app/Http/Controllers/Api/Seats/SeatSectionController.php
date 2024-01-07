<?php

namespace App\Http\Controllers\Api\Seats;

use App\Http\Controllers\Controller;
use App\Models\SeatSection;
use Illuminate\Http\Request;

/**
 * @group Seats
 */
class SeatSectionController extends Controller
{
  public function index(Request $request)
  {
    $seatSections = SeatSection::query()
      ->withCount('seats')
      ->get();
    return $this->apiRes($seatSections);
  }
}
