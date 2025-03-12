<?php

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatSection;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of seat sections', function () {
  $event = Event::factory()->create();
  SeatSection::factory(4)
    ->eventPackages($event)
    ->create();
  getJson(route('api.seat-sections.index'))
    // ->dump()
    ->assertOk()
    ->assertJsonCount(4, 'data')
    ->assertJsonStructure([
      'data' => [
        '*' => ['id', 'title', 'capacity', 'seats_count']
      ]
    ]);
});

it('can create a new seat section', function () {
  $postData = SeatSection::factory()
    ->make()
    ->toArray();
  $response = actingAs($this->admin)->postJson(
    route('api.seat-sections.store'),
    $postData
  );
  $response->assertOk()->assertJsonFragment($postData);
});

it('can create a new seat section with seats', function () {
  $postData = SeatSection::factory()
    ->make()
    ->toArray();
  $response = actingAs($this->admin)->postJson(
    route('api.seat-sections.store'),
    [
      ...$postData,
      'seats' => Seat::factory(2)
        ->make()
        ->toArray()
    ]
  );
  $response
    ->assertOk()
    ->assertJsonFragment($postData)
    ->assertJsonStructure([
      'data' => [
        'title',
        'description',
        'capacity',
        'seats' => [
          '*' => ['id', 'seat_no', 'description', 'features', 'status']
        ]
      ]
    ]);
});

it('can update an existing seat section', function () {
  $seatSection = SeatSection::factory()->create();
  $postData = [
    'title' => 'Updated Seat Section',
    'description' => 'Updated description',
    'capacity' => 100
  ];
  $response = actingAs($this->admin)->postJson(
    route('api.seat-sections.update', [$seatSection->id]),
    $postData
  );
  $response->assertOk()->assertJsonFragment($postData);
});
