<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Enums\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
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

    if ($paymentReference->merchant !== PaymentMerchantType::Paystack) {
      return $this->ok(
        failRes('', ['slug' => 'unexpected_merchant'])->toArray()
      );
    }

    $res = PaymentProcessor::make(
      $paymentReference
    )->handleCallbackWithTransaction();

    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
