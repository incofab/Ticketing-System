<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Actions\GenerateTicketFromPayment;
use App\Actions\GetAvailableSeats;
use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use App\Rules\ValidateSeatAvailabilityRule;
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

    $data = $request->validate([
      'reference' => ['required', 'string'],
      'quantity' => ['sometimes', 'integer', 'min:1'],
      'seats' => ['sometimes', 'array', 'min:1'],
      'seats.*.seat_id' => [
        'required',
        new ValidateSeatAvailabilityRule($ticketPayment)
      ],
      'seats.*.attendee' => ['sometimes', 'array', 'min:1'],
      'seats.*.attendee.name' => ['sometimes', 'string'],
      'seats.*.attendee.email' => ['sometimes', 'email'],
      'seats.*.attendee.phone' => ['sometimes', 'string'],
      'seats.*.attendee.address' => ['sometimes', 'string']
    ]);

    $selectedSeatIds = collect($data['seats'] ?? [])
      ->map(fn($item) => $item['seat_id'])
      ->toArray();

    $existingTicketsGenerated = $ticketPayment->tickets()->count();
    $remainingSeats = $ticketPayment->quantity - $existingTicketsGenerated;

    abort_if(
      $remainingSeats < 1,
      403,
      'Your payment is not enough for the requested number of seats'
    );

    $seatIds = $this->getSeatIds(
      $request,
      $selectedSeatIds,
      $ticketPayment,
      $remainingSeats
    );

    $tickets = (new GenerateTicketFromPayment(
      $paymentReference,
      $seatIds,
      $data['seats'] ?? []
    ))->run();

    return $this->apiRes($tickets);
  }

  /**
   * Returns all the seat ids to be generated
   *  @return int[]
   * */
  private function getSeatIds(
    Request $request,
    array|null $selectedSeatIds,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    if ($selectedSeatIds && $request->quantity) {
      return $this->handleQuantityAndSeatIds(
        $request,
        $selectedSeatIds,
        $ticketPayment,
        $remainingSeats
      );
    } elseif ($selectedSeatIds) {
      return $this->handleSeatIdsOnly(
        $request,
        $selectedSeatIds,
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
    array|null $selectedSeatIds,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    abort_if(
      $remainingSeats < count($selectedSeatIds),
      403,
      'Your payment is not enough for the requested number of seats'
    );
    return $selectedSeatIds;
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
    array $selectedSeatIds,
    TicketPayment $ticketPayment,
    int $remainingSeats
  ) {
    $count = count($selectedSeatIds) + $request->quantity;
    abort_if(
      $remainingSeats < $count,
      403,
      'Your payment is not enough for the requested number of seats'
    );
    $seatsForQuantity = GetAvailableSeats::run($ticketPayment->eventPackage)
      ->whereNotIn('seats.id', $selectedSeatIds)
      ->oldest('seats.seat_no')
      ->take($request->quantity)
      ->pluck('seats.id')
      ->toArray();
    return array_merge($selectedSeatIds, $seatsForQuantity);
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
      // ->oldest('seats.seat_no')
      ->take($count)
      ->pluck('seats.id')
      ->toArray();
  }
}
