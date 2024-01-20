<?php
use App\Models\Event;
use App\Models\EventImage;
use App\Models\EventPackage;
use App\Models\EventSeason;
use App\Models\SeatSection;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotEmpty;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of events', function () {
  // Create some dummy data, for example:
  $eventSeason = EventSeason::factory()->create();
  Event::factory(5)->create(['event_season_id' => $eventSeason->id]);
  Event::factory(2)->create();

  // Call the endpoint
  getJson(route('api.events.index', ['eventSeason' => $eventSeason->id]))
    ->assertOk()
    ->assertJsonCount(5, 'data.data')
    ->assertJsonStructure([
      'data' => [
        'data' => [
          '*' => [
            'id',
            'title',
            'event_season_id',
            'expired',
            'event_packages',
            'event_images'
          ]
        ]
      ]
    ]);
  getJson(route('api.events.index'))
    ->assertOk()
    ->assertJsonCount(7, 'data.data');
});

it('can get a list of upcoming events', function () {
  // Create some dummy data, for example:
  $eventSeason = EventSeason::factory()->create();
  $events = Event::factory(5)->create([
    'event_season_id' => $eventSeason->id,
    'start_time' => now()->addDays(1)
  ]);

  $response = getJson(
    route('api.events.upcoming', ['eventSeason' => $eventSeason->id])
  );
  $response->assertOk()->assertJsonCount(5, 'data.data');
});

it('can show an event', function () {
  $event = Event::factory()->create();
  EventPackage::factory(2)
    ->event($event)
    ->create();
  EventImage::factory(2)
    ->event($event)
    ->create();
  $event->load('eventSeason', 'eventPackages', 'eventImages');
  getJson(route('api.events.show', $event))
    ->assertOk()
    ->assertJson([
      'data' => [
        'id' => $event->id,
        'title' => $event->title,
        'event_season' => $event->eventSeason->only('id', 'title'),
        'event_packages' => $event->eventPackages->toArray(),
        'event_images' => $event->eventImages->toArray()
      ]
    ]);
});
// return;
it('can store a new event', function () {
  // Create some dummy data, for example:
  $eventSeason = EventSeason::factory()->create();
  [$seatSection, $seatSection2] = SeatSection::factory(2)->create();

  // Call the endpoint with valid data
  $response = actingAs($this->admin)->postJson(
    route('api.events.store', ['eventSeason' => $eventSeason->id]),
    [
      'title' => 'New Event',
      'description' => 'Event description',
      'start_time' => now(),
      'end_time' => now()->addDays(1),
      'home_team' => 'Home Team',
      'away_team' => 'Away Team',
      'event_packages' => [
        [
          'seat_section_id' => $seatSection->id,
          'price' => 50.0
        ],
        [
          'seat_section_id' => $seatSection2->id,
          'price' => 150.0
        ]
      ]
    ]
  );

  // Assert the response
  $eventData = $response
    ->assertOk()
    ->assertJsonFragment(['title' => 'New Event'])
    ->json('data');
  $event = Event::where('id', $eventData['id'])
    ->with('eventPackages')
    ->first();
  assertNotEmpty($event);
  expect($event->eventPackages->count())->toBe(2);
});

it('can update an existing event', function () {
  // Create some dummy data, for example:
  $event = Event::factory()->create();

  // Call the endpoint with valid data for update
  $response = actingAs($this->admin)->postJson(
    route('api.events.update', ['event' => $event->id]),
    [
      'title' => 'Updated Event',
      'description' => 'Updated description',
      'start_time' => now(),
      'end_time' => now()->addDays(2),
      'home_team' => 'Updated Home Team',
      'away_team' => 'Updated Away Team'
    ]
  );

  // Assert the response
  $response->assertOk()->assertJsonFragment(['title' => 'Updated Event']);
});

it('can delete an existing event', function () {
  $event = Event::factory()->create();
  $response = actingAs($this->admin)->postJson(
    route('api.events.destroy', ['event' => $event->id])
  );
  // Assert the response
  $response
    ->assertOk()
    ->assertJson(['message' => 'Event deleted successfully']);

  assertSoftDeleted('events', ['id' => $event->id]);
});
