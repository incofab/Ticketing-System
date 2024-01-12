<?php

namespace App\Http\Controllers\Api\Events;

use App\Actions\CreateUpdateEventPackage;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPackage;
use Illuminate\Http\Request;

/**
 * @group Event Packages
 */
class EventPackageController extends Controller
{
  public function index(Request $request)
  {
    $query = EventPackage::query()
      ->eventId($request->event)
      ->seatSectionId($request->seatSection);
    return $this->apiRes(paginateFromRequest($query));
  }

  public function store(Request $request, Event $event)
  {
    $data = $request->validate([
      'seat_section_id' => ['required', 'exists:seat_sections,id'],
      'price' => ['required', 'numeric'],
      'entry_gate' => ['nullable', 'string']
    ]);
    $createdPackages = CreateUpdateEventPackage::run($event, [$data]);
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
