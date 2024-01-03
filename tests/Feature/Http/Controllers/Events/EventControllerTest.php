<?php
use App\Models\Event;
use App\Models\EventSeason;
use App\Models\SeatSection;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertNotEmpty;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of events', function () {
  // Create some dummy data, for example:
  $eventSeason = EventSeason::factory()->create();
  $events = Event::factory(5)->create(['event_season_id' => $eventSeason->id]);

  // Call the endpoint
  $response = getJson(
    route('api.events.index', ['eventSeason' => $eventSeason->id])
  );

  // Assert the response
  $response->assertOk()->assertJsonCount(5, 'data.data');
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
