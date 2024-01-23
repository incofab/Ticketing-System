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
   *
   * @queryParam sortKey string Represents the direction of the sort. Must be either of ASC|DESC. No-example
   * @queryParam sortDir string. No-example
   * @queryParam search string. No-example
   * @queryParam date_from string. No-example
   * @queryParam date_to string. No-example
   */
  public function index(Request $request)
  {
    $query = TicketPayment::query()->select(
      'ticket_payments.*',
      'payment_references.merchant'
    );
    TicketPaymentUITableFilters::make($request->all(), $query)->filterQuery();
    /*
    $request->validate([
      'status' => ['nullable', new Enum(PaymentReferenceStatus::class)],
      'event_package_id' => ['nullable', 'exists:event_package_id,id'],
      'event_id' => ['nullable', 'exists:events,id']
    ]);

    $query = TicketPayment::query()
      ->select('ticket_payments.*', 'payment_references.merchant')
      ->join('payment_references', function ($q) {
        $q->on(
          'payment_references.paymentable_id',
          'ticket_payments.id'
        )->where(
          'payment_references.paymentable_type',
          (new TicketPayment())->getMorphClass()
        );
      })
      // ->join('event_packages', 'event_packages.id', 'ticket_payments.event_package_id')
      // ->join('seat_sections', 'seat_sections.id', 'event_packages.seat_section_id')
      ->when(
        $request->event_id,
        fn($q, $value) => $q
          ->join(
            'event_packages',
            'event_packages.id',
            'ticket_payments.event_package_id'
          )
          ->where('event_packages.event_id', $value)
      )
      ->when(
        $request->status,
        fn($q, $value) => $q->where('payment_references.status', $value)
      )
      ->when(
        $request->event_package_id,
        fn($q, $value) => $q->where('ticket_payments.event_package_id', $value)
      )
      */
    $query
      ->with('eventPackage.event', 'eventPackage.seatSection')
      ->oldest('ticket_payments.id');

    return $this->apiRes(paginateFromRequest($query, 300));
  }
}
