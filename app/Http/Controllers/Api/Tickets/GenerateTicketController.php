<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Actions\GenerateTicketFromPayment;
use App\Actions\GetAvailableSeats;
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
      'quantity' => ['sometimes', 'integer', 'min:1'],
      'seat_ids' => ['sometimes', 'array', 'min:1'],
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
      $remainingSeats < 1,
      403,
      'Your payment is not enough for the requested number of seats'
    );

    $seatIds = $this->getSeatIds($request, $ticketPayment, $remainingSeats);

    $tickets = (new GenerateTicketFromPayment(
      $paymentReference,
      $seatIds
    ))->run();

    return $this->apiRes($tickets);
  }

  /**
   * Returns all the seat ids to be generated
   *  @return int[]
   * */
  private function getSeatIds(
    Request $request,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    if ($request->seat_ids && $request->quantity) {
      return $this->handleQuantityAndSeatIds(
        $request,
        $ticketPayment,
        $remainingSeats
      );
    } elseif ($request->seat_ids) {
      return $this->handleSeatIdsOnly(
        $request,
        $ticketPayment,
        $remainingSeats
      );
    } elseif ($request->quantity) {
      return $this->handleQuantityOnly(
        $request,
        $ticketPayment,
        $remainingSeats
      );
    } else {
      return $this->handleForAllRemainingSeats($ticketPayment, $remainingSeats);
    }
  }

  /** @return int[] */
  private function handleSeatIdsOnly(
    Request $request,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    abort_if(
      $remainingSeats < count($request->seat_ids),
      403,
      'Your payment is not enough for the requested number of seats'
    );
    return $request->seat_ids;
  }

  private function handleQuantityOnly(
    Request $request,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    abort_if(
      $remainingSeats < $request->quantity,
      403,
      'Your payment is not enough for the requested number of seats'
    );
    return $this->getAvailableSeats($ticketPayment, $request->quantity);
  }

  /** @return int[] */
  private function handleQuantityAndSeatIds(
    Request $request,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    $count = count($request->seat_ids) + $request->quantity;
    abort_if(
      $remainingSeats < $count,
      403,
      'Your payment is not enough for the requested number of seats'
    );
    $seatsForQuantity = GetAvailableSeats::run($ticketPayment->eventPackage)
      ->whereNotIn('seats.id', $request->seat_ids)
      ->oldest('seats.seat_no')
      ->take($request->quantity)
      ->pluck('seats.id')
      ->toArray();
    return array_merge($request->seat_ids, $seatsForQuantity);
  }

  /** @return int[] */
  private function handleForAllRemainingSeats(
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    return $this->getAvailableSeats($ticketPayment, $remainingSeats);
  }

  private function getAvailableSeats(TicketPayment $ticketPayment, int $count)
  {
    return GetAvailableSeats::run($ticketPayment->eventPackage)
      ->oldest('seats.seat_no')
      ->take($count)
      ->pluck('seats.id')
      ->toArray();
  }
}
