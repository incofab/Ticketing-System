<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
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

    $tickets = paginateFromRequest(
      $ticketPayment
        ->tickets()
        ->getQuery()
        ->with('seat.seatSection')
    );

    return $this->apiRes([
      'tickets' => $tickets,
      'payment' => $ticketPayment
    ]);
  }
}
