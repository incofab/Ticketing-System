<?php

use App\Models\User;
use App\Models\Event;
use App\Models\Coupon;
use App\Models\EventPackage;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->admin = User::factory()
    ->admin()
    ->create();
  $this->event = Event::factory()->create();
  $this->packages = EventPackage::factory(2)
    ->event($this->event)
    ->create();
  $this->coupon = Coupon::factory()
    ->event($this->event)
    ->create();
});

/**
 * INDEX
 */
it('returns a paginated list of coupons for an event', function () {
  actingAs($this->admin)
    ->getJson(route('api.coupons.index', ['event' => $this->event->id]))
    ->assertOk()
    ->assertJsonStructure([
      'data' => [
        'data' => [
          '*' => ['id', 'code', 'discount_type', 'discount_value']
        ]
      ]
    ]);
});

it('creates a new coupon for an event', function () {
  $data = Coupon::factory()
    ->event($this->event)
    ->fixed()
    ->make([
      'code' => 'NEWCOUPON2025',
      'discount_value' => 100,
      'min_purchase' => 200,
      'expires_at' => Carbon::now()
        ->addDays(7)
        ->toDateTimeString(),
      'usage_limit' => 10,
      'usage_count' => 0,
      'event_package_ids' => $this->packages->pluck('id')->toArray()
    ])
    ->toArray();

  actingAs($this->admin)
    ->postJson(route('api.coupons.store', ['event' => $this->event->id]), $data)
    ->assertOk()
    ->assertJsonFragment(['code' => 'NEWCOUPON2025']);
});

it('shows a single coupon', function () {
  actingAs($this->admin)
    ->getJson(
      route('api.coupons.show', [
        'event' => $this->event->id,
        'coupon' => $this->coupon->code
      ])
    )
    ->assertOk()
    ->assertJsonFragment(['id' => $this->coupon->id]);
});

it('updates an existing coupon', function () {
  $updateData = Coupon::factory()
    ->event($this->event)
    ->fixed()
    ->make([
      'code' => 'UPDATEDCODE',
      'discount_value' => 250,
      'min_purchase' => 100,
      'expires_at' => Carbon::now()
        ->addDays(3)
        ->toDateTimeString(),
      'usage_limit' => 5,
      'usage_count' => 0,
      'event_package_ids' => $this->packages->pluck('id')->toArray()
    ])
    ->toArray();

  actingAs($this->admin)
    ->postJson(
      route('api.coupons.update', ['coupon' => $this->coupon->id]),
      $updateData
    )
    ->assertOk()
    ->assertJsonFragment(['code' => 'UPDATEDCODE']);
});

/**
 * DESTROY
 */
it('deletes a coupon that has no ticket payments', function () {
  actingAs($this->admin)
    ->postJson(route('api.coupons.destroy', ['coupon' => $this->coupon->id]))
    ->assertOk();

  expect(Coupon::find($this->coupon->id))->toBeNull();
});

it('fails to delete a coupon that has ticket payments', function () {
  $this->coupon->ticketPayments()->create([
    'event_package_id' => $this->packages->first()->id,
    'quantity' => 1,
    'original_amount' => 1000,
    'discount_amount' => 100,
    'amount' => 900
  ]);

  actingAs($this->admin)
    ->postJson(route('api.coupons.destroy', ['coupon' => $this->coupon->id]))
    ->assertForbidden()
    ->assertJsonFragment([
      'message' => 'Cannot delete coupon with associated payments.'
    ]);
});
