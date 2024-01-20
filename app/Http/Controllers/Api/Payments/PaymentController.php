<?php

namespace App\Http\Controllers\Api\Payments;

use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\TicketPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Payments
 */
class PaymentController extends Controller
{
  public function index(Request $request)
  {
    $request->validate([
      'status' => ['nullable', new Enum(PaymentReferenceStatus::class)],
      'event_package_id' => ['nullable', 'exists:event_package_id,id'],
      'event_id' => ['nullable', 'exists:events,id']
    ]);

    $query = TicketPayment::query()
      ->select('ticket_payments.*')
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
      ->with('eventPackage.event', 'eventPackage.seatSection')
      ->oldest('ticket_payments.id');

    return $this->apiRes(paginateFromRequest($query, 300));
  }
}
