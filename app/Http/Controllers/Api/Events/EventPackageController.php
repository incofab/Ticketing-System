<?php

namespace App\Http\Controllers\Api\Events;

use App\Actions\CreateUpdateEventPackage;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventPackageRequest;
use App\Models\Event;
use App\Models\EventPackage;
use Illuminate\Http\Request;

/**
 * @group Event Packages
 */
class EventPackageController extends Controller
{
  /**
   * @queryParam event int No-example
   * @queryParam seatSection int No-example
   */
  public function index(Request $request)
  {
    $query = EventPackage::query()
      ->eventId($request->event)
      ->seatSectionId($request->seatSection);
    return $this->apiRes(paginateFromRequest($query));
  }

  public function store(CreateEventPackageRequest $request, Event $event)
  {
    // $data = $request->validate([
    //   'seat_section_id' => ['required', 'exists:seat_sections,id'],
    //   'price' => ['required', 'numeric'],
    //   'entry_gate' => ['nullable', 'string'],
    //   'notes' => ['nullable', 'string'],
    //   'capacity' => [
    //     'required',
    //     'integer',
    //     function ($attr, $value, $fail) {
    //       /** @var SeatSection $seatSection */
    //       $seatSection = SeatSection::query()->findOrFail(
    //         request('seat_section_id')
    //       );
    //       $allocatedCapacity = EventPackage::whereSeatSectionId(
    //         $seatSection->id
    //       )->sum('capacity');
    //       $availableCapacity = $seatSection->capacity - $allocatedCapacity;
    //       if ($availableCapacity < 1) {
    //         $fail('There are no available seats');
    //         return;
    //       }
    //       if ($availableCapacity < $value) {
    //         $fail("There are only $availableCapacity available seat capacity");
    //         return;
    //       }
    //     }
    //   ],
    //   'title' => [
    //     'required',
    //     'string',
    //     Rule::unique('event_packages', 'title')->where(
    //       'seat_section_id',
    //       $request->seat_section_id
    //     )
    //   ]
    // ]);
    $data = $request->validated();
    $createdPackages = CreateUpdateEventPackage::run($event, [$data]);
    return $this->apiRes($createdPackages[0]);
  }

  function update(
    CreateEventPackageRequest $request,
    EventPackage $eventPackage
  ) {
    $data = $request->validated();
    $createdPackages = CreateUpdateEventPackage::run($eventPackage->event, [
      $data
    ]);
    return $this->apiRes($createdPackages[0]);
  }

  public function destroy(EventPackage $eventPackage)
  {
    abort_if(
      $eventPackage->ticketPayments()->exists(),
      403,
      'Cannot delete a package that has payments initiated on it'
    );
    $eventPackage->delete();
    return $this->message('Event package deleted successfully');
  }
}
