<?php
use App\Models\Seat;
use App\Models\SeatSection;
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
