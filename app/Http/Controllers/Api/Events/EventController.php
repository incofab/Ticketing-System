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

  /**
   * @bodyParam title string required The title of the event. Example: My Event
   * @bodyParam description string The description of the event. Example: This is a great event
   * @bodyParam start_time datetime required The start time of the event. Example: 2024-12-31 12:00:00
   * @bodyParam end_time datetime The end time of the event. Must be after start_time. Example: 2024-12-31 14:00:00
   * @bodyParam home_team string The home team for the event. Example: Home Team
   * @bodyParam away_team string The away team for the event. Example: Away Team
   * @bodyParam venue string The venue of the event. Example: Event Hall
   * @bodyParam phone string The contact phone number. Example: +1234567890
   * @bodyParam email string The contact email address. Example: contact@example.com
   * @bodyParam website string The event website. Example: https://www.example.com
   * @bodyParam facebook string The Facebook page URL. Example: https://www.facebook.com/example
   * @bodyParam twitter string The Twitter page URL. Example: https://www.twitter.com/example
   * @bodyParam instagram string The Instagram page URL. Example: https://www.instagram.com/example
   * @bodyParam youtube string The YouTube channel URL. Example: https://www.youtube.com/example
   * @bodyParam tiktok string The TikTok profile URL. Example: https://www.tiktok.com/@example
   * @bodyParam linkedin string The LinkedIn profile URL. Example: https://www.linkedin.com/in/example
   * @bodyParam logo file The event logo image.
   * @bodyParam payment_merchants array An array of payment merchant types. Example: ["paypal", "stripe"]
   * @bodyParam payment_merchants.* string required A valid payment merchant type.
   * @bodyParam meta array Additional metadata for the event.
   * @bodyParam meta.extra_user_data array An array of extra data fields.
   * @bodyParam meta.extra_user_data array An array of extra user data fields.
   * @bodyParam meta.extra_user_data.*.name string required The name of the extra user data field. Example: Address
   * @bodyParam meta.extra_user_data.*.type string required The type of the extra user data field. Example: must be one of: text, long-text, integer, float
   * @bodyParam meta.extra_user_data.*.is_required boolean required Whether the extra user data field is required. Example: true
   *
   * @bodyParam event_packages array An array of event packages.
   * @bodyParam event_packages.*.seat_section_id integer required The ID of the seat section.
   * @bodyParam event_packages.*.price numeric required The price of the event package.
   * @bodyParam event_packages.*.title string required The title of the event package. Example: VIP Package
   */
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

  /**
   * @bodyParam title string required The title of the event. Example: My Event
   * @bodyParam description string The description of the event. Example: This is a great event
   * @bodyParam start_time datetime required The start time of the event. Example: 2024-12-31 12:00:00
   * @bodyParam end_time datetime The end time of the event. Must be after start_time. Example: 2024-12-31 14:00:00
   * @bodyParam home_team string The home team for the event. Example: Home Team
   * @bodyParam away_team string The away team for the event. Example: Away Team
   * @bodyParam venue string The venue of the event. Example: Event Hall
   * @bodyParam phone string The contact phone number. Example: +1234567890
   * @bodyParam email string The contact email address. Example: contact@example.com
   * @bodyParam website string The event website. Example: https://www.example.com
   * @bodyParam facebook string The Facebook page URL. Example: https://www.facebook.com/example
   * @bodyParam twitter string The Twitter page URL. Example: https://www.twitter.com/example
   * @bodyParam instagram string The Instagram page URL. Example: https://www.instagram.com/example
   * @bodyParam youtube string The YouTube channel URL. Example: https://www.youtube.com/example
   * @bodyParam tiktok string The TikTok profile URL. Example: https://www.tiktok.com/@example
   * @bodyParam linkedin string The LinkedIn profile URL. Example: https://www.linkedin.com/in/example
   * @bodyParam logo file The event logo image.
   * @bodyParam payment_merchants array An array of payment merchant types. Example: ["paypal", "stripe"]
   * @bodyParam payment_merchants.* string required A valid payment merchant type.
   * @bodyParam meta array Additional metadata for the event.
   * @bodyParam meta.extra_user_data array An array of extra data fields.
   * @bodyParam meta.extra_user_data array An array of extra user data fields.
   * @bodyParam meta.extra_user_data.*.name string required The name of the extra user data field. Example: Address
   * @bodyParam meta.extra_user_data.*.type string required The type of the extra user data field. Example: must be one of: text, long-text, integer, float
   * @bodyParam meta.extra_user_data.*.is_required boolean required Whether the extra user data field is required. Example: true
   */
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
