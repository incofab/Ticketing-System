<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use App\Models\EventSeason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Event Seasons
 */
class EventSeasonController extends Controller
{
  public function index(EventCategory $eventCategory = null)
  {
    $eventSeasons = $eventCategory
      ? $eventCategory->eventSeasons()
      : EventSeason::query();
    return $this->apiRes(paginateFromRequest($eventSeasons));
  }

  public function upcomingSeason(EventCategory $eventCategory = null)
  {
    $query = $eventCategory
      ? $eventCategory->eventSeasons()->getQuery()
      : EventSeason::query();

    return $this->apiRes(
      paginateFromRequest($query->upcomingSeason()->oldest('date_from'))
    );
  }

  public function store(Request $request, EventCategory $eventCategory)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('event_seasons', 'title')->where(
          'event_category_id',
          $eventCategory->id
        )
      ],
      'description' => ['nullable', 'string'],
      'date_from' => ['sometimes', 'required_with:date_to', 'date'],
      'date_to' => ['sometimes', 'date', 'gt:date_from']
    ]);

    $eventSeason = $eventCategory->eventSeasons()->create($data);

    return $this->apiRes($eventSeason);
  }

  public function update(Request $request, EventSeason $eventSeason)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('event_seasons', 'title')
          ->where('event_category_id', $eventSeason->event_category_id)
          ->ignore($eventSeason->id, 'id')
      ],
      'description' => ['nullable', 'string'],
      'date_from' => ['sometimes', 'required_with:date_to', 'date'],
      'date_to' => ['sometimes', 'date', 'gt:date_from']
    ]);

    $eventSeason->fill($data)->save();
    return $this->apiRes($eventSeason);
  }

  public function destroy(EventSeason $eventSeason)
  {
    abort_if(
      $eventSeason->events()->exists(),
      403,
      'Cannot delete a season that contain some events'
    );
    $eventSeason->delete();
    return $this->message('Event season deleted successfully');
  }
}
