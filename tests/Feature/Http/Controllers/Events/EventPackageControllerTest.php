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
  $requestData = ['price' => 50.0, 'entry_gate' => 'Gate 1'];

  actingAs($this->admin)
    ->postJson(route('api.event-packages.store', ['event' => $event->id]), [
      'seat_section_id' => $seatSection->id,
      ...$requestData
    ])
    ->assertOk()
    ->assertJsonFragment(['price' => 50.0]);
  assertDatabaseHas('event_packages', [
    'event_id' => $event->id,
    ...$requestData
  ]);
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
