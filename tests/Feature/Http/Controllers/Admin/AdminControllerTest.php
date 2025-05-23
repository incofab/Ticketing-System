<?php

use App\Enums\PaymentReferenceStatus;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventPackage;
use App\Models\EventSeason;
use App\Models\PaymentReference;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;
use App\Models\TicketVerification;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->seed(RoleSeeder::class);
  $this->admin = User::factory()
    ->admin()
    ->create();
  //   $this->event = Event::factory()->create();
  //   $this->eventPackage = EventPackage::factory()->create([
  //     'event_id' => $this->event->id
  //   ]);
  //   $this->seat = Seat::factory()->create();
});

it('should return the correct dashboard data', function () {
  EventSeason::factory()
    ->count(3)
    ->create();
  $events = Event::factory()
    ->count(2)
    ->create();
  $seats = Seat::factory()
    ->count(5)
    ->create();
  User::factory()
    ->count(4)
    ->create();

  $response = actingAs($this->admin)->getJson(route('api.admin.dashboard'));

  $response->assertStatus(200);
  $response->assertJson([
    'data' => [
      'seats_count' => $seats->count(),
      'events_count' => $events->count(),
      'event_seasons_count' => EventSeason::query()->count(),
      'users_count' => User::query()->count()
    ]
  ]);
});

it('should return the correct event dashboard data', function () {
  actingAs($this->admin);
  $event = Event::factory()->create();

  $eventPackage1 = EventPackage::factory()->create([
    'event_id' => $event->id
  ]);
  $eventPackage2 = EventPackage::factory()->create([
    'event_id' => $event->id
  ]);

  $ticket = Ticket::factory()
    ->eventPackage($eventPackage1)
    ->create();
  EventAttendee::factory(3)
    ->ticket($ticket)
    ->create();

  $ticketPayment1 = TicketPayment::factory()->create([
    'event_package_id' => $eventPackage1->id,
    'quantity' => 2
  ]);

  $ticketPayment2 = TicketPayment::factory()->create([
    'event_package_id' => $eventPackage2->id,
    'quantity' => 3
  ]);

  PaymentReference::factory()
    ->ticketPayment($ticketPayment1)
    ->confirmed()
    ->create(['amount' => 2000]);

  PaymentReference::factory()
    ->ticketPayment($ticketPayment2)
    ->confirmed()
    ->create(['amount' => 3000]);

  TicketVerification::factory(2)
    ->for($ticket)
    ->create();

  getJson(route('api.admin.event.dashboard', ['event' => $event->id]))
    ->assertOk()
    ->assertJson([
      'data' => [
        'total_income' => 5000,
        'tickets_sold' => 5,
        'packages' => 2,
        'attendees' => 3,
        'verified_attendees' => 2,
        'total_package_capacity' =>
          $eventPackage1->capacity + $eventPackage2->capacity,
        'project_revenue' =>
          $eventPackage1->capacity * $eventPackage1->price +
          $eventPackage2->capacity * $eventPackage2->price
      ]
    ]);
});

it('should deny access if user is not an admin', function () {
  $user = User::factory()->create();
  $event = Event::factory()
    ->for($user)
    ->create();
  $nonAdmin = User::factory()->create();
  actingAs($user)
    ->getJson(route('api.admin.event.dashboard', $event))
    ->assertOk();
  actingAs($nonAdmin)
    ->getJson(route('api.admin.event.dashboard', ['event' => $event->id]))
    ->assertStatus(403);
});
