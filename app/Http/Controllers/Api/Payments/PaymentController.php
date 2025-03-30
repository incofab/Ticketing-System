<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\TicketPayment;
use App\Support\UITableFilters\TicketPaymentUITableFilters;
use Illuminate\Http\Request;

/**
 * @group Payments
 */
class PaymentController extends Controller
{
  /**
   * @queryParam status string No-example
   * @queryParam event_package_id int. No-example
   * @queryParam event_id int. No-example
   * @queryParam merchant string. No-example
   * @queryParam email string. No-example
   *
   * @queryParam sortKey string No-example
   * @queryParam sortDir string Represents the direction of the sort. ASC|DESC. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function index(Request $request)
  {
    $query = TicketPayment::query()->select(
      'ticket_payments.*',
      'payment_references.status',
      'payment_references.merchant'
    );
    TicketPaymentUITableFilters::make($request->all(), $query)->filterQuery();
    $query
      ->with('eventPackage.event', 'eventPackage.seatSection')
      ->latest('ticket_payments.id');

    return $this->apiRes(paginateFromRequest($query, 300));
  }
}
