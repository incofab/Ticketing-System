<?php
namespace Database\Seeders;

use App\Models\SeatSection;
use Illuminate\Database\Seeder;

/**
 * Seeds the seat seat-sections and their seats
 */
class SeatSectionSeeder extends Seeder
{
  const SECTIONS = [
    [
      'id' => 1,
      'title' => 'Regular',
      'capacity' => 10000,
      'description' => null
    ],
    [
      'id' => 2,
      'title' => 'Cover Stand Regular',
      'capacity' => 2083,
      'description' => null
    ],
    [
      'id' => 3,
      'title' => 'Cover Stand Executive',
      'capacity' => 60,
      'description' => null
    ],
    [
      'id' => 4,
      'title' => 'Press Gallery',
      'capacity' => 110,
      'description' => null
    ]
  ];

  /** @return array{ 'id': int, 'seat_section_id': int, 'seat_no': string, 'description': string}[] */
  function getSeats(SeatSection $seatSection)
  {
    $seats = [];
    for ($i = 1; $i <= $seatSection->capacity; $i++) {
      $seats[] = [
        'seat_no' => str_pad($i, 4, '0', STR_PAD_LEFT)
      ];
    }
    return $seats;
  }
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    foreach (self::SECTIONS as $key => $category) {
      $seatSection = SeatSection::query()->firstOrCreate(
        ['title' => $category['title']],
        $category
      );
      $seats = $this->getSeats($seatSection);
      foreach ($seats as $key => $seat) {
        $seatSection
          ->seats()
          ->firstOrCreate(['seat_no' => $seat['seat_no']], $seat);
      }
    }
  }
}
