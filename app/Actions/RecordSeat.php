<?php
namespace App\Actions;

use App\Models\SeatSection;

class RecordSeat
{
  function __construct(private SeatSection $seatSection)
  {
  }

  /**
   * @param array{ 'seat_no': string, 'description': string, 'features': string, 'status': string}
   * @return Seat $seat
   */
  function create(array $seat)
  {
    return $this->seatSection
      ->seats()
      ->firstOrCreate(['seat_no' => $seat['seat_no']], $seat);
  }
  /**
   * @param array{ 'seat_no': string, 'description': string, 'features': string, 'status': string}[]
   * @return Seat[] $createdSeats
   */
  function createMany(array $seats)
  {
    $createdSeats = [];
    foreach ($seats as $key => $seat) {
      $createdSeats[] = $this->create($seat);
    }
    return $createdSeats;
  }
}
