<?php

use App\Enums\RoleType;
use App\Models\User;
use App\Models\Ticket;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->admin = User::factory()
    ->role()
    ->create();
  Ticket::factory(5)
    ->ticketPayment()
    ->create();
});

it('allows only admins to access this route', function () {
  $normalUser = User::factory()->create();
  $manager = User::factory()
    ->role(RoleType::Manager)
    ->create();
  actingAs($normalUser)
    ->getJson(route('api.tickets.index'))
    ->assertForbidden();
  actingAs($manager)
    ->getJson(route('api.tickets.index'))
    ->assertForbidden();
  actingAs($this->admin)
    ->getJson(route('api.tickets.index'))
    ->assertok();
});

it('returns a paginated list of tickets', function () {
  actingAs($this->admin)
    ->getJson(route('api.tickets.index'))
    ->assertOk()
    ->assertJsonStructure([
      'data' => [
        'data' => [
          '*' => [
            'seat_id',
            'seat' => ['id', 'seat_no', 'seat_section' => ['title']],
            'qr_code',
            'ticket_payment' => ['quantity'],
            'event_package' => ['price', 'event' => ['title']]
          ]
        ]
      ]
    ]);
});
