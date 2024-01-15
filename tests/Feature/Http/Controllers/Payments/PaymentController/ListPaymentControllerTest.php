<?php

use App\Models\PaymentReference;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->admin = User::factory()
    ->role()
    ->create();
  PaymentReference::factory(5)
    ->ticketPayment()
    ->confirmed()
    ->create();
  PaymentReference::factory(2)
    ->ticketPayment()
    ->create();
});

it('returns a paginated list of payments', function () {
  actingAs($this->admin)
    ->getJson(route('api.payments.index'))
    ->assertOk()
    ->assertJsonCount(5, 'data.data')
    ->assertJsonStructure([
      'data' => [
        'data' => [
          '*' => ['event_package_id', 'quantity']
        ]
      ]
    ]);
});
