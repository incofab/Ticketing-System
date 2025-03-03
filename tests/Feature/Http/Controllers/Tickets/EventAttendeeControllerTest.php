<?php

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->user = User::factory()->create();
  $this->event = Event::factory()->create();
  $this->seatSection = SeatSection::factory()->create();
  $this->eventPackage = EventPackage::factory()->create([
    'event_id' => $this->event->id,
    'seat_section_id' => $this->seatSection->id,
    'capacity' => 10,
    'price' => 1000
  ]);
  $this->seats = Seat::factory(5)->create([
    'seat_section_id' => $this->seatSection->id
  ]);
  $this->ticket = Ticket::factory()->create([
    'event_package_id' => $this->eventPackage->id,
    'user_id' => $this->user->id
  ]);
});

it('can get a list of event packages', function () {
  $user = User::factory()
    ->admin()
    ->create();
  actingAs($user);

  EventAttendee::factory(5)
    ->for($this->event)
    ->create();

  getJson(
    route('api.event-attendees.index', [
      'event' => $this->event->id
    ])
  )
    ->assertOk()
    ->assertJsonCount(5, 'data.data');
});

it('should store an event attendee', function () {
  // actingAs($this->user);

  $attendeeData = EventAttendee::factory()
    ->make([
      'ticket_id' => $this->ticket->id,
      'event_id' => $this->ticket->eventPackage->event_id
    ])
    ->toArray();

  postJson(
    route('api.tickets.event-attendees.store', [$this->ticket->id]),
    $attendeeData
  )->assertStatus(200);

  $this->assertDatabaseHas('event_attendees', $attendeeData);
});

it('should validate the name field', function () {
  $attendeeData = [
    'email' => 'john.doe@example.com',
    'phone' => '1234567890',
    'address' => '123 Main St'
  ];

  actingAs($this->user)
    ->postJson(
      route('api.tickets.event-attendees.store', [
        'ticket' => $this->ticket->id
      ]),
      $attendeeData
    )
    ->assertJsonValidationErrorFor('name');
});
