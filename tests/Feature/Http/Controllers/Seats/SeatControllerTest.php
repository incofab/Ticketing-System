<?php

use App\Enums\SeatStatus;
use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of event ', function () {
  $seatSection = SeatSection::factory()->create();
  Seat::factory(4)->create();
  Seat::factory(4)
    ->seatSection($seatSection)
    ->create();

  getJson(route('api.seats.index'))
    ->assertOk()
    ->assertJsonCount(8, 'data.data');

  getJson(
    route('api.seats.index', [
      'seatSection' => $seatSection->id
    ])
  )
    ->assertOk()
    ->assertJsonCount(4, 'data.data');
});

it('returns available seats for the designated event package', function () {
  // Create an event package and associated seat
  $eventPackage = EventPackage::factory()->create();
  [$seats1] = Seat::factory(10)->create([
    'seat_section_id' => $eventPackage->seat_section_id
  ]);
  // Create a ticket within a different event package
  Ticket::factory(3)->create();
  Ticket::factory(3)
    ->eventPackage($eventPackage)
    ->create();
  Ticket::factory()
    ->eventPackage($eventPackage)
    ->create([
      'event_package_id' => $eventPackage->id,
      'seat_id' => $seats1->id
    ]);
  // dd(Seat::all()->count() . ' djdskds');
  // Make a request to the available seats endpoint
  getJson(route('api.seats.available', [$eventPackage]))
    ->assertOk()
    // ->dump()
    ->assertJsonCount(9, 'data.data')
    ->assertJsonStructure([
      'data' => [
        'data' => [
          '*' => ['id', 'seat_section_id']
        ]
      ]
    ]);
});

// Add more tests to cover different scenarios, such as no available seats, etc.

it('can store a new seat in a seat section and handle validation', function () {
  // Create a seat section with some capacity
  $seatSection = SeatSection::factory()->create(['capacity' => 5]);

  // Valid data
  $validData = Seat::factory(2)
    ->seatSection($seatSection)
    ->make()
    ->map(
      fn($item) => collect($item)
        ->except('seat_section_id')
        ->toArray()
    )
    ->toArray();

  // Test successful creation
  postJson(route('api.seats.store', $seatSection), [
    'seats' => $validData
  ])->assertOk();
  $this->assertDatabaseHas('seats', [
    ...$validData[0],
    'seat_section_id' => $seatSection->id
  ]);
  $this->assertDatabaseHas('seats', [
    ...$validData[1],
    'seat_section_id' => $seatSection->id
  ]);

  // Test creating a duplicate seat
  postJson(route('api.seats.store', $seatSection), [
    'seats' => $validData
  ])->assertOk();
  $this->assertDatabaseCount('seats', 2);

  // Test creating the seat with just the required value
  postJson(route('api.seats.store', $seatSection), [
    'seats' => [['seat_no' => 'A2']]
  ])->assertOk();
  $this->assertDatabaseHas('seats', [
    'seat_section_id' => $seatSection->id,
    'seat_no' => 'A2',
    'description' => null,
    'status' => SeatStatus::Available->value
  ]);

  // Test validation: seat_no required
  postJson(route('api.seats.store', $seatSection), [
    'seats' => [['description' => 'test']]
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors('seats.0.seat_no');

  $seatSection->fill(['capacity' => SeatSection::query()->count()])->save();
  // Test section full
  postJson(route('api.seats.store', $seatSection), [
    'seats' => [['seat_no' => 'A6']]
  ])
    ->assertStatus(403)
    ->assertSee('Seat section is full');
});
