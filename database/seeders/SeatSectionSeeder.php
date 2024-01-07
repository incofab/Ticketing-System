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
    ['title' => 'section-d', 'capacity' => 2253, 'description' => 'Section D']
  ];

  /** @return array{ 'seat_section_id': int, 'seat_no': string, 'description': string}[] */
  function getSeats(SeatSection $seatSection)
  {
    return [
        // [
        //   'seat_no' => rand(10000, 99999),
        //   'description' => null
        // ]
      ];
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
