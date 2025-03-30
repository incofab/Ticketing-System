<?php

namespace App\Http\Controllers\Api\Tickets;

use App\DTO\PaymentReferenceDto;
use App\Enums\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Support\Payment\PaymentMerchant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Tickets
 */
class InitTicketPurchaseController extends Controller
{
  public function __invoke(EventPackage $eventPackage, Request $request)
  {
    $eventPackage->load('seatSection', 'event');
    $data = $request->validate([
      'merchant' => [
        'required',
        'string',
        new Enum(PaymentMerchantType::class)
      ],
      'callback_url' => [
        'nullable',
        'string',
        Rule::requiredIf(
          fn() => $request->merchant !== PaymentMerchantType::BankDeposit->value
        )
      ],
      'quantity' => [
        'required',
        'integer',
        'min:1',
        function ($attr, $value, $fail) use ($eventPackage) {
          $availableSeats =
            $eventPackage->capacity - $eventPackage->quantity_sold;
          if ($availableSeats < 1) {
            $fail('All seats in this package are fully booked');
            return;
          }
          if ($availableSeats < $value) {
            $fail("There are only $availableSeats seat(s)");
            return;
          }
        }
      ],
      'name' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255']
    ]);
    abort_if($eventPackage->event->isExpired(), 403, 'Event is expired');
    $amount = $eventPackage->price * $data['quantity'];

    $ticketPayment = $eventPackage->ticketPayments()->create([
      ...collect($data)
        ->except('callback_url', 'merchant')
        ->toArray(),
      'user_id' => currentUser()?->id
    ]);

    $reference = PaymentReference::generateReference();
    $paymentReferenceDto = new PaymentReferenceDto(
      $request->merchant,
      $ticketPayment,
      $amount,
      $reference,
      $ticketPayment->user_id
    );
    $paymentReferenceDto->setCallbackUrl($request->callback_url);
    [$res, $paymentReference] = PaymentMerchant::make($request->merchant)->init(
      $paymentReferenceDto
    );
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
