<?php

namespace App\Http\Controllers\Api\Seats;

use App\Actions\RecordSeat;
use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\SeatSection;
use App\Support\UITableFilters\SeatSectionUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Seats
 */
class SeatSectionController extends Controller
{
  /**
   * @queryParam event int Representing the event Id. No-example
   *
   * @queryParam sortKey string No-example
   * @queryParam sortDir string Represents the direction of the sort. ASC|DESC. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function index(Request $request)
  {
    // SeatSection::query()->innerJoin
    $query = SeatSectionUITableFilters::make(
      $request->all(),
      SeatSection::select('seat_sections.*')
    )
      ->filterQuery()
      ->getQuery();
    $seatSections = paginateFromRequest(
      $query
        ->withCount('seats', 'eventPackages')
        ->with('eventPackages')
        ->withSum('eventPackages', 'quantity_sold')
    );
    return $this->apiRes($seatSections);
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        'unique:seat_sections,title'
      ],
      'description' => ['nullable', 'string'],
      'features' => ['nullable', 'string'],
      'capacity' => ['required', 'integer'],
      'seats' => ['nullable', 'array', 'min:1'],
      ...Seat::createRule('seats.*.')
    ]);

    abort_if(
      $data['capacity'] < count($data['seats'] ?? []),
      403,
      'Seat section is full'
    );

    $seatSection = SeatSection::query()->create(
      collect($data)
        ->except('seats')
        ->toArray()
    );
    if (!empty($data['seats'])) {
      (new RecordSeat($seatSection))->createMany($data['seats']);
    }
    $seatSection->load('seats');

    return $this->apiRes($seatSection);
  }

  public function update(Request $request, SeatSection $seatSection)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('seat_sections', 'title')->ignore($seatSection->id, 'id')
      ],
      'description' => ['nullable', 'string'],
      'features' => ['nullable', 'string'],
      'capacity' => ['required', 'integer']
    ]);

    $seatSection->fill($data)->save();
    return $this->apiRes($seatSection);
  }
}
