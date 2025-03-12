<?php

namespace App\Http\Controllers\Api\Seats;

use App\Actions\GetAvailableSeats;
use App\Actions\RecordSeat;
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
  public function index(Request $request, SeatSection|null $seatSection = null)
  {
    $seatSections = Seat::query()->seatSectionId($seatSection?->id);
    return $this->apiRes(paginateFromRequest($seatSections));
  }

  public function available(Request $request, EventPackage $eventPackage)
  {
    $query = GetAvailableSeats::run($eventPackage);
    return $this->apiRes(paginateFromRequest($query));
  }

  /**
   * Store new seats in a seat section.
   *
   * This endpoint allows you to create multiple seats within a specific seat section.
   *
   * @bodyParam seats array required An array of seats to create.
   * @bodyParam seats[].seat_no string required The seat number (e.g., "A1", "B5").
   * @bodyParam seats[].description string The seat description. Example: "Near the aisle"
   * @bodyParam seats[].features string The seat features. Example: "Premium seat with extra legroom"
   * @bodyParam seats[].status string The seat status. Possible values : available, reserved, blocked. Example: available
   */
  function store(Request $request, SeatSection $seatSection)
  {
    $data = $request->validate([
      'seats' => ['required', 'array', 'min:1'],
      ...Seat::createRule('seats.*.')
    ]);

    abort_if(
      $seatSection->seats()->count() + count($data['seats']) >=
        $seatSection->capacity,
      403,
      'Seat section is full'
    );
    $createdSeats = (new RecordSeat($seatSection))->createMany($data['seats']);
    // foreach ($data['seats'] as $key => $seat) {
    //   $createdSeats[] = $seatSection
    //     ->seats()
    //     ->firstOrCreate(['seat_no' => $seat['seat_no']], $seat);
    // }
    return $this->apiRes($createdSeats, 'Seats created successfully');
  }
}
