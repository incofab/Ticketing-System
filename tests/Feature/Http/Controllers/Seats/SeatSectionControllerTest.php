<?php
use App\Models\SeatSection;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of seat sections', function () {
  SeatSection::factory(4)->create();
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
