<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Actions\GenerateTicketFromPayment;
use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class GenerateTicketController extends Controller
{
  public function __invoke(Request $request)
  {
    $paymentReference = PaymentReference::query()
      ->where('reference', $request->reference)
      ->where('status', PaymentReferenceStatus::Confirmed)
      ->with([
        'paymentable' => function (MorphTo $morphTo) {
          $morphTo->morphWith([
            TicketPayment::class => ['eventPackage.seatSection']
          ]);
        }
      ])
      ->firstOrFail();

    /** @var TicketPayment $ticketPayment */
    $ticketPayment = $paymentReference->paymentable;

    $request->validate([
      'reference' => ['required', 'string'],
      'seat_ids' => ['required', 'array', 'min:1'],
      'seat_ids.*' => [
        'required',
        function ($attr, $value, $fail) use ($ticketPayment) {
          // First check that the seat exists in the selected package
          $seat = Seat::query()
            ->where('id', $value)
            ->where(
              'seat_section_id',
              $ticketPayment->eventPackage->seat_section_id
            )
            ->first();
          if (!$seat) {
            $fail("$attr not available in this section of payment");
            return;
          }
          // Check if the seats has been booked already
          $existingTicket = Ticket::query()
            ->where('event_package_id', $ticketPayment->event_package_id)
            ->where('tickets.seat_id', $value)
            ->first();
          if ($existingTicket) {
            $fail("$attr has already been booked");
            return;
          }
        }
      ]
    ]);

    $existingTicketsGenerated = $ticketPayment->tickets()->count();
    $remainingSeats = $ticketPayment->quantity - $existingTicketsGenerated;

    abort_if(
      $remainingSeats < count($request->seat_ids),
      403,
      'Your payment is not enough for the requested number of seats'
    );

    $tickets = (new GenerateTicketFromPayment(
      $paymentReference,
      $request->seat_ids
    ))->run();

    return $this->apiRes($tickets);
  }
}
