<?php
use App\Models\SeatSection;

use function Pest\Laravel\getJson;

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
