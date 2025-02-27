<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Enums\PaymentMerchantType;
use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Models\PaymentReference;
use App\Support\Payment\Processor\PaymentProcessor;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class ConfirmBankDepositController extends Controller
{
  public function __invoke(Request $request)
  {
    $request->validate(['reference' => ['required', 'string']]);

    abort_unless(
      currentUser()->hasRole([RoleType::Manager, RoleType::Admin]),
      403,
      'Access denied'
    );

    /** @var PaymentReference $paymentReference */
    $paymentReference = PaymentReference::query()
      ->where('reference', $request->reference)
      ->where('merchant', PaymentMerchantType::BankDeposit)
      ->firstOrFail();

    $res = PaymentProcessor::make($paymentReference)->handleCallback();

    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
