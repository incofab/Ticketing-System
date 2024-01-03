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
    $eventPackage->load('seatSection');
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
          fn() => $request->merchant === PaymentMerchantType::Paystack->value
        )
      ],
      'quantity' => [
        'required',
        'integer',
        'min:1',
        function ($attr, $value, $fail) use ($eventPackage) {
          $availableSeats =
            $eventPackage->seatSection->capacity - $eventPackage->quantity_sold;
          if ($availableSeats < 1) {
            $fail('All seats in this section are fully booked');
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
      'email' => ['nullable', 'email', 'max:255']
    ]);

    $amount = $eventPackage->price * $data['quantity'];

    $ticketPayment = $eventPackage->ticketPayments()->create([
      ...collect($data)
        ->except('callback_url')
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
    [$res, $paymentReference] = PaymentMerchant::make($request->merchant)->init(
      $paymentReferenceDto
    );
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
