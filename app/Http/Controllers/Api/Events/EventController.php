<?php

namespace App\Http\Controllers\Api\Events;

use App\Actions\CreateUpdateEventPackage;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeason;
use App\Support\UITableFilters\EventUITableFilters;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Events
 */
class EventController extends Controller
{
  /**
   * @queryParam title string No-example
   * @queryParam start_time_from string No-example
   * @queryParam start_time_to string No-example
   *
   * @queryParam sortKey string No-example
   * @queryParam sortDir string Represents the direction of the sort. ASC|DESC. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function index(Request $request, EventSeason $eventSeason = null)
  {
    $query = $eventSeason ? $eventSeason->events()->getQuery() : Event::query();

    $query->selectRaw('*, (start_time < NOW()) AS expired');

    EventUITableFilters::make($request->all(), $query)->filterQuery();

    return $this->apiRes(
      paginateFromRequest(
        $query->with('eventImages', 'eventPackages')->oldest('start_time')
      )
    );
  }

  public function upcomingEvents(EventSeason $eventSeason = null)
  {
    $query = $eventSeason ? $eventSeason->events()->getQuery() : Event::query();

    return $this->apiRes(
      paginateFromRequest($query->upcomingEvents()->oldest('start_time'))
    );
  }

  public function show(Event $event)
  {
    $event->load('eventSeason', 'eventPackages.seatSection', 'eventImages');
    return $this->apiRes($event);
  }

  public function store(Request $request, EventSeason $eventSeason)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('events', 'title')->where(
          'event_season_id',
          $eventSeason->id
        )
      ],
      'description' => ['nullable', 'string'],
      'start_time' => ['sometimes', 'required', 'date'],
      'end_time' => ['sometimes', 'date', 'after:start_time'],
      'home_team' => ['nullable', 'string'],
      'away_team' => ['nullable', 'string'],
      'event_packages' => ['nullable', 'array', 'min:1'],
      'event_packages.*.seat_section_id' => [
        'required',
        'exists:seat_sections,id'
      ],
      'event_packages.*.price' => ['required', 'numeric']
    ]);

    $event = $eventSeason->events()->create(
      collect($data)
        ->except('event_packages')
        ->toArray()
    );
    CreateUpdateEventPackage::run($event, $data['event_packages'] ?? []);
    return $this->apiRes($event);
  }

  public function update(Request $request, Event $event)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('events', 'title')
          ->where('event_season_id', $event->event_season_id)
          ->ignore($event->id, 'id')
      ],
      'description' => ['nullable', 'string'],
      'start_time' => ['sometimes', 'required', 'date'],
      'end_time' => ['sometimes', 'date', 'after:start_time'],
      'home_team' => ['nullable', 'string'],
      'away_team' => ['nullable', 'string']
    ]);

    $event->fill($data)->save();
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
