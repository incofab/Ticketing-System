<?php

use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\getJson;

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
