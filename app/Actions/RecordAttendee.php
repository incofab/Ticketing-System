<?php
namespace App\Actions;

use App\Models\EventAttendee;
use App\Models\EventPackage;
use App\Models\Ticket;

class RecordAttendee
{
  /**
   * @param array{ 'event_id': int, 'price': float, title: string, notes: string, capacity: integer}[]
   * @return EventPackage[] $createdPackages
   */
  static function run(Ticket $ticket, array $data)
  {
    return EventAttendee::query()->firstOrCreate(
      [
        'ticket_id' => $ticket->id,
        'event_id' => $ticket->eventPackage->event_id
      ],
      $data
    );
  }
}
