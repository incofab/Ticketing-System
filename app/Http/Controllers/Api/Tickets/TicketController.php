<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Mail\TicketPurchaseMail;
use App\Models\Ticket;
use App\Models\TicketPayment;
use App\Support\MorphMap;
use Illuminate\Http\Request;

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

  function resendTicket(Request $request)
  {
    $request->validate([
      'email' => ['required', 'email'],
      'event_id' => ['required', 'exists:events,id']
    ]);
    $ticketPayments = TicketPayment::query()
      ->join(
        'event_packages',
        'ticket_payments.event_package_id',
        '=',
        'event_packages.id'
      )
      ->join('payment_references', function ($join) {
        $join
          ->on('payment_references.paymentable_id', 'ticket_payments.id')
          ->where(
            'payment_references.paymentable_type',
            MorphMap::key(TicketPayment::class)
          );
      })
      ->where('event_packages.event_id', $request->event_id)
      ->where('ticket_payments.email', $request->email)
      ->where('payment_references.status', PaymentReferenceStatus::Confirmed)
      ->with('tickets')
      ->get();
    if ($ticketPayments->isEmpty()) {
      return $this->apiRes([], 'No tickets found', 404);
    }
    $count = 0;
    foreach ($ticketPayments as $key1 => $ticketPayment) {
      foreach ($ticketPayment->tickets as $key2 => $ticket) {
        if ($count >= 50) {
          break;
        }
        $count++;
        \Mail::to($ticketPayment->email)->queue(
          new TicketPurchaseMail($ticket)
        );
      }
    }
    return $this->ok();
  }
}
