<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\UITableFilters\TicketUITableFilters;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class ListTicketController extends Controller
{
  /**
   * @queryParam reference string No-example
   * @queryParam ticket_payment_id int No-example
   * @queryParam event_package_id int No-example
   * @queryParam seat_id int No-example
   *
   * @queryParam sortKey string. No-example
   * @queryParam sortDir string Represents the direction of the sort. ASC|DESC. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function __invoke(Request $request)
  {
    $query = TicketUITableFilters::make($request->all(), Ticket::query())
      ->filterQuery()
      ->getQuery()
      ->latest('id')
      ->with('ticketPayment', 'eventPackage.event', 'seat.seatSection');

    return $this->apiRes(paginateFromRequest($query));
  }
}
