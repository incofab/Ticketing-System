<?php

namespace App\Http\Controllers\Api\Events;

use App\Actions\CreateUpdateEventPackage;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeason;
use App\Support\UITableFilters\EventUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Storage;

/**
 * @group Events
 */
class EventController extends Controller
{
  /**
   * @queryParam title string No-example
   * @queryParam start_time_from string No-example
   * @queryParam start_time_to string No-example
   * @queryParam for_upcoming boolean No-example
   * @queryParam for_past boolean No-example
   *
   * @queryParam sortKey string No-example
   * @queryParam sortDir string Represents the direction of the sort. ASC|DESC. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function index(Request $request, EventSeason|null $eventSeason = null)
  {
    $query = $eventSeason ? $eventSeason->events()->getQuery() : Event::query();

    $user = currentUser();
    // $forUser = $request->for_user && $user; // && !$user?->isAdmin();
    $query
      ->select('events.*')
      ->when($user, fn($q) => $q->where('events.user_id', $user?->id))
      ->when($request->for_upcoming, fn($q) => $q->upcomingEvents())
      ->when($request->for_past, fn($q) => $q->pastEvents());

    EventUITableFilters::make($request->all(), $query)->filterQuery();

    return $this->apiRes(
      paginateFromRequest(
        $query
          ->with('eventImages', 'eventPackages')
          ->oldest('events.start_time')
      )
    );
  }

  public function upcomingEvents(EventSeason|null $eventSeason = null)
  {
    $query = $eventSeason ? $eventSeason->events()->getQuery() : Event::query();

    return $this->apiRes(
      paginateFromRequest(
        $query
          ->upcomingEvents()
          ->with('eventSeason', 'eventPackages.seatSection', 'eventImages')
          ->oldest('start_time')
      )
    );
  }

  public function show(Event $event)
  {
    $event->load(
      'eventSeason.eventCategory',
      'eventPackages.seatSection',
      'eventImages'
    );
    return $this->apiRes($event);
  }

  public function store(Request $request, EventSeason $eventSeason)
  {
    $data = $request->validate([
      ...Event::createRule($eventSeason->id),
      'event_packages' => ['nullable', 'array', 'min:1'],
      'event_packages.*.seat_section_id' => [
        'required',
        'exists:seat_sections,id'
      ],
      'event_packages.*.price' => ['required', 'numeric'],
      'event_packages.*.title' => ['required', 'string']
    ]);

    $event = $eventSeason->events()->create([
      ...collect($data)
        ->except('event_packages', 'logo')
        ->toArray(),
      'user_id' => currentUser()?->id
    ]);
    CreateUpdateEventPackage::run($event, $data['event_packages'] ?? []);

    $this->uploadLogo($event, $request->logo);

    return $this->apiRes($event);
  }

  private function uploadLogo(Event $event, UploadedFile|null $file = null)
  {
    if (!$file) {
      return;
    }
    $imagePath = $file->store("event_{$event->id}", 's3_public');
    $publicUrl = Storage::disk('s3_public')->url($imagePath);
    $event->fill(['logo' => $publicUrl])->save();
  }

  public function update(Request $request, Event $event)
  {
    $data = $request->validate(
      Event::createRule($event->event_season_id, $event)
    );

    $event
      ->fill(
        collect($data)
          ->except('logo')
          ->toArray()
      )
      ->save();

    $this->uploadLogo($event, $request->logo);

    return $this->apiRes($event);
  }

  public function destroy(Event $event)
  {
    abort_if(
      $event->eventPackages()->exists(),
      403,
      'Cannot delete an event whose prices have been set'
    );
    $event->delete();
    return $this->message('Event deleted successfully');
  }
}
