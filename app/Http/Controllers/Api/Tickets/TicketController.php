<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;

/**
 * @group Tickets
 */
class TicketController extends Controller
{
  public function showByReference(Ticket $ticket)
  {
    $ticket->load(
      'seat.seatSection',
      'eventPackage.event.eventImages',
      'eventAttendee',
      'ticketVerification'
    );

    return $this->apiRes([
      'ticket' => $ticket
    ]);
  }
}
