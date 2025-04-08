<?php
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\SeatSection;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of event packages', function () {
  $event = Event::factory()->create();
  $seatSection = SeatSection::factory()->create();
  $eventPackages = EventPackage::factory(5)->create([
    'event_id' => $event->id,
    'seat_section_id' => $seatSection->id
  ]);

  getJson(
    route('api.event-packages.index', [
      'event' => $event->id,
      'seatSection' => $seatSection->id
    ])
  )
    ->assertOk()
    ->assertJsonCount(5, 'data.data');
});

it('can store a new event package', function () {
  $event = Event::factory()->create();
  $seatSection = SeatSection::factory()->create();
  // $requestData = ['price' => 50.0, 'entry_gate' => 'Gate 1'];
  $requestData = EventPackage::factory()
    ->make(['seat_section_id' => $seatSection->id, 'event_id' => $event->id])
    ->toArray();

  actingAs($this->admin)
    ->postJson(
      route('api.event-packages.store', ['event' => $event->id]),
      $requestData
    )
    ->assertOk()
    ->assertJsonFragment(['price' => $requestData['price']]);
  assertDatabaseHas('event_packages', $requestData);
});

it('cannot store a new event package when capacity is not enough', function () {
  $event = Event::factory()->create();
  $seatSection = SeatSection::factory()->create(['capacity' => 10]);
  $requestData = EventPackage::factory()
    ->for($seatSection)
    ->event($event)
    ->make(['capacity' => 6])
    ->toArray();

  actingAs($this->admin)
    ->postJson(route('api.event-packages.store', ['event' => $event->id]), [
      ...$requestData,
      'capacity' => 11
    ])
    ->assertJsonValidationErrorFor('capacity');
  EventPackage::factory()
    ->for($seatSection)
    ->event($event)
    ->create(['capacity' => 5]);

  actingAs($this->admin)
    ->postJson(
      route('api.event-packages.store', ['event' => $event->id]),
      $requestData
    )
    ->assertJsonValidationErrorFor('capacity');
});

it('can delete an existing event package', function () {
  $eventPackage = EventPackage::factory()->create();
  $response = actingAs($this->admin)->postJson(
    route('api.event-packages.destroy', ['eventPackage' => $eventPackage->id])
  );
  $response
    ->assertOk()
    ->assertJson(['message' => 'Event package deleted successfully']);

  assertDatabaseMissing('event_packages', ['id' => $eventPackage->id]);
});

it('can update an existing event package', function () {
  $event = Event::factory()->create();
  $seatSection = SeatSection::factory()->create();
  $eventPackage = EventPackage::factory()
    ->event($event)
    ->for($seatSection)
    ->create();
  $requestData = [
    ...$eventPackage->toArray(),
    'price' => 100.0,
    'capacity' => 15
  ];
  actingAs($this->admin)
    ->postJson(
      route('api.event-packages.update', ['eventPackage' => $eventPackage->id]),
      $requestData
    )
    ->assertOk();
  assertDatabaseHas(
    'event_packages',
    collect($requestData)
      ->except('created_at', 'updated_at')
      ->toArray()
  );
});
