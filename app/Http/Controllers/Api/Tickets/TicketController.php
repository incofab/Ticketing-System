<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Mail\TicketPurchaseMail;
use App\Models\Ticket;
use App\Models\TicketPayment;
use App\Support\MorphMap;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
      'email' => [Rule::requiredIf(empty($request->reference)), 'email'],
      'event_id' => [
        Rule::requiredIf(empty($request->reference)),
        'exists:events,id'
      ],
      'reference' => [Rule::requiredIf(empty($request->email)), 'string']
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
      ->when(
        $request->reference,
        fn($q) => $q->where('payment_references.reference', $request->reference)
      )
      ->when(
        $request->event_id,
        fn($q) => $q->where('event_packages.event_id', $request->event_id)
      )
      ->when(
        $request->email,
        fn($q) => $q->where('ticket_payments.email', $request->email)
      )
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

  function printTicket(Ticket $ticket, Request $request)
  {
    $ticket->load(
      'seat.seatSection',
      'eventPackage.event.eventImages',
      'eventAttendee',
      'ticketVerification'
    );
    return view('tickets.ticket-view-pdf', [
      'ticket' => $ticket,
      'seat' => $ticket->seat,
      'eventPackage' => $ticket->eventPackage,
      'seatSection' => $ticket->eventPackage->seatSection,
      'event' => $ticket->eventPackage->event
    ]);
  }

  function delete(Ticket $ticket, Request $request)
  {
    $ticket->load(
      'eventPackage.event',
      'eventAttendee', // handled
      'ticketVerification', // handled
      'seat.seatSection'
    );
    $user = currentUser();
    abort_unless(
      $user->isAdmin() || $ticket->eventPackage->event->user_id == $user->id,
      403,
      'Access denied'
    );
    $eventPackage = $ticket->eventPackage;
    DB::beginTransaction();
    if (!$ticket->delete()) {
      return $this->error('Failed to delete ticket');
    }
    $ticketPayment = $ticket->ticketPayment;
    $ticketPayment->paymentReferences()->delete();
    $ticketPayment->delete();
    $eventPackage
      ->fill(['quantity_sold' => $eventPackage->quantity_sold - 1])
      ->save();

    DB::commit();
    return $this->ok();
  }
}
