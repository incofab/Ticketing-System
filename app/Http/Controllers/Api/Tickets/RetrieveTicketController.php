<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class RetrieveTicketController extends Controller
{
  public function __invoke(Request $request)
  {
    $request->validate([
      'reference' => ['required', 'string'],
      'email' => ['required', 'email']
    ]);

    $paymentReference = PaymentReference::query()
      ->where('reference', $request->reference)
      ->where('status', PaymentReferenceStatus::Confirmed)
      ->with('paymentable')
      ->first();

    /** @var TicketPayment $ticketPayment */
    $ticketPayment = $paymentReference?->paymentable;
    $ticketPayment?->load('eventPackage');
    abort_if(
      !$paymentReference || $ticketPayment?->email !== $request->email,
      403,
      'Invalid reference and/or email supplied'
    );

    $query = Ticket::query()
      ->select('tickets.*')
      ->join('seats', 'tickets.seat_id', 'seats.id')
      ->where('tickets.ticket_payment_id', $ticketPayment->id)
      ->groupBy('tickets.seat_id')
      ->oldest('seats.seat_no')
      ->with(
        'seat.seatSection',
        'eventPackage.event.eventImages',
        'eventAttendee',
        'ticketVerification'
      );

    $tickets = paginateFromRequest($query);

    return $this->apiRes([
      'tickets' => $tickets,
      'payment' => $ticketPayment
    ]);
  }
}
