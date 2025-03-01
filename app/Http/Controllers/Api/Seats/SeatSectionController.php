<?php

namespace App\Http\Controllers\Api\Seats;

use App\Http\Controllers\Controller;
use App\Models\SeatSection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
      'capacity' => ['required', 'integer']
    ]);

    $seatSection = SeatSection::query()->create($data);
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
