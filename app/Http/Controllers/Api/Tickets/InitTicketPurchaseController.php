<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Actions\GenerateTicketFromPayment;
use App\DTO\PaymentReferenceDto;
use App\Enums\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Rules\ValidateExistsRule;
use App\Support\Payment\PaymentMerchant;
use App\Support\Payment\Processor\PaymentProcessor;
use App\Support\Res;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Tickets
 * Initialize Ticket Purchase
 *
 * This endpoint initializes the purchase of tickets for a specific event package.
 *
 * @urlParam eventPackage int required The ID of the event package. Example: 1
 * @bodyParam merchant string required The payment merchant to use. Example: paystack
 * @bodyParam callback_url string The URL to redirect to after payment. Required for non-bank deposit and non-free merchants. Example: https://example.com/callback
 * @bodyParam quantity int required The number of tickets to purchase. Example: 2
 * @bodyParam name string The name of the ticket purchaser. Example: John Doe
 * @bodyParam phone string The phone number of the ticket purchaser. Example: 08012345678
 * @bodyParam email string required The email of the ticket purchaser. Example: john.doe@example.com
 * @bodyParam referral_code string The referral code used for the purchase. Example: REF123
 */
class InitTicketPurchaseController extends Controller
{
  public function __invoke(EventPackage $eventPackage, Request $request)
  {
    if ($eventPackage->price <= 0) {
      $request->merge([
        'merchant' => PaymentMerchantType::Free->value,
        'quantity' => 1
      ]);
    }
    $couponVal = new ValidateExistsRule(Coupon::class, 'code');
    $eventPackage->load('seatSection', 'event');
    $data = $request->validate([
      'coupon_code' => ['nullable', 'string', $couponVal],
      'merchant' => [
        'required',
        'string',
        new Enum(PaymentMerchantType::class),
        function ($attr, $value, $fail) use ($eventPackage) {
          if (
            $eventPackage->price > 0 &&
            request('merchant') === PaymentMerchantType::Free->value
          ) {
            $fail('This is not a free package');
            return;
          }
        }
      ],
      'callback_url' => [
        'nullable',
        'string',
        Rule::requiredIf(
          fn() => !in_array($request->merchant, [
            PaymentMerchantType::BankDeposit->value,
            PaymentMerchantType::Free->value
          ])
        )
      ],
      'quantity' => [
        'required',
        'integer',
        'min:1',
        function ($attr, $value, $fail) use ($eventPackage) {
          if (
            $value > 1 &&
            request('merchant') === PaymentMerchantType::Free->value
          ) {
            $fail('You cannot get more than one ticket at a time');
            return;
          }
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
      'email' => ['required', 'email', 'max:255'],
      'referral_code' => ['nullable', 'string', 'max:255'],
      'receivers' => ['nullable', 'array'],
      'receivers.*' => ['string', 'email', 'max:255']
    ]);

    abort_if($eventPackage->event->isExpired(), 403, 'Event is expired');

    /** @var Coupon $coupon */
    $coupon = $couponVal->getModel();
    $unitDiscount = $coupon?->getDiscount($eventPackage->price);
    $price = $eventPackage->price - $unitDiscount;
    $amount = $price * $data['quantity'];

    abort_if($amount < 0, 403, 'Invalid purchase amount');

    $ticketPayment = $eventPackage->ticketPayments()->create([
      ...collect($data)
        ->except('callback_url', 'merchant', 'coupon_code')
        ->toArray(),
      'user_id' => currentUser()?->id,
      'coupon_id' => $coupon?->id,
      'amount' => $amount,
      'original_amount' => $eventPackage->price * $data['quantity'],
      'discount_amount' => $unitDiscount * $data['quantity']
    ]);
    $reference = PaymentReference::generateReference();
    $paymentReferenceDto = new PaymentReferenceDto(
      merchant: $request->merchant,
      paymentable: $ticketPayment,
      amount: $amount,
      reference: $reference,
      user_id: $ticketPayment->user_id,
      callback_url: $request->callback_url
    );
    // $paymentReferenceDto->setCallbackUrl($request->callback_url);
    [$res, $paymentReference] = PaymentMerchant::make($request->merchant)->init(
      $paymentReferenceDto
    );

    $res = $this->handleResult($paymentReference, $res);
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }

  function handleResult(PaymentReference $paymentReference, Res $res)
  {
    if ($paymentReference->merchant !== PaymentMerchantType::Free) {
      return $res;
    }
    [$res] = PaymentProcessor::make($paymentReference)->handleCallback();
    if (!$res->isSuccessful()) {
      return $res;
    }
    $tickets = GenerateTicketFromPayment::generateFromPaymentReference(
      $paymentReference
    );
    $res->reference = $paymentReference->reference;
    $res->tickets = $tickets;
    return $res;
  }
}
