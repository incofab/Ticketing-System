<?php

namespace App\Http\Controllers\Api\Seats;

use App\Actions\GetAvailableSeats;
use App\Actions\RecordSeat;
use App\Http\Controllers\Controller;
use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\SeatSection;
use App\Support\UITableFilters\SeatUITableFilters;
use Illuminate\Http\Request;

/**
 * @group Seats
 */
class SeatController extends Controller
{
  /**
   * @queryParam seat_section_id int Representing the seatSection Id. No-example
   * @queryParam status string. No-example
   * @queryParam seat_no string. No-example
   *
   * @queryParam sortKey string No-example
   * @queryParam sortDir string Represents the direction of the sort. ASC|DESC. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function index(Request $request, SeatSection|null $seatSection = null)
  {
    $query = SeatUITableFilters::make(
      $request->all(),
      Seat::query()->seatSectionId($seatSection?->id)
    )
      ->filterQuery()
      ->getQuery();
    // $seatSections = Seat::query()->seatSectionId($seatSection?->id);
    return $this->apiRes(paginateFromRequest($query));
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
      $seatSection->seats()->count() + count($data['seats']) >
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
