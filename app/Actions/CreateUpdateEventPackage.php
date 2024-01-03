<?php
namespace App\Actions;

use App\Models\Event;
use App\Models\EventPackage;

class CreateUpdateEventPackage
{
  /**
   * @param array{ 'seat_section_id': int, 'price': float}[]
   * @return EventPackage[] $createdPackages
   */
  static function run(Event $event, array $eventPackagesData)
  {
    $createdPackages = [];
    foreach ($eventPackagesData as $key => $eventPackage) {
      $createdPackages[] = $event
        ->eventPrices()
        ->updateOrCreate(
          ['seat_section_id' => $eventPackage['seat_section_id']],
          $eventPackage
        );
    }
    return $createdPackages;
  }
}
