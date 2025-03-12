<?php

use App\Models\User;
use App\Models\Ticket;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->admin = User::factory()
    ->role()
    ->create();
  $this->ticket = Ticket::factory()->create();
});

it('shows ticket by reference', function () {
  getJson(route('api.tickets.show.reference', [$this->ticket->reference]))
    ->assertOk()
    ->assertJsonStructure([
      'data' => [
        'ticket' => [
          'seat' => ['id', 'seat_no', 'seat_section' => ['title']],
          'qr_code',
          'event_package' => ['price', 'event' => ['title']]
        ]
      ]
    ]);
});
