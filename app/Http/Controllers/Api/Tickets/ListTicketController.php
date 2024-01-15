<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class ListTicketController extends Controller
{
  public function __invoke(Request $request)
  {
    $query = Ticket::query()
      ->latest('id')
      ->with('ticketPayment', 'eventPackage.event', 'seat.seatSection');

    return $this->apiRes(paginateFromRequest($query));
  }
}
