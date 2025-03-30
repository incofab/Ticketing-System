<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Actions\GenerateTicketFromPayment;
use App\Actions\GetAvailableSeats;
use App\Enums\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use App\Support\Payment\Processor\PaymentProcessor;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class ConfirmPaymentController extends Controller
{
  public function __invoke(Request $request)
  {
    $request->validate(['reference' => ['required', 'string']]);

    /** @var PaymentReference $paymentReference */
    $paymentReference = PaymentReference::query()
      ->where('reference', $request->reference)
      // ->where('merchant', PaymentMerchantType::Paystack)
      ->firstOrFail();

    // if ($paymentReference->merchant !== PaymentMerchantType::Paystack) {
    //   return $this->ok(
    //     failRes('', ['slug' => 'unexpected_merchant'])->toArray()
    //   );
    // }

    $res = PaymentProcessor::make($paymentReference)->handleCallback();

    abort_unless($res->isSuccessful(), 403, $res->getMessage());

    $tickets = $this->generateTickets($paymentReference);

    return $this->ok([...$res->toArray(), 'tickets' => $tickets]);
  }

  private function generateTickets(PaymentReference $paymentReference)
  {
    /** @var TicketPayment $ticketPayment */
    $ticketPayment = $paymentReference->paymentable;
    $existingTicketsGenerated = $ticketPayment->tickets()->count();
    $remainingSeats = $ticketPayment->quantity - $existingTicketsGenerated;
    if ($remainingSeats < 1) {
      return $ticketPayment->tickets()->get();
    }
    $seatIds = GetAvailableSeats::run($ticketPayment->eventPackage)
      ->take($ticketPayment->quantity)
      ->pluck('seats.id')
      ->toArray();
    return (new GenerateTicketFromPayment($paymentReference, $seatIds))->run();
  }
}
