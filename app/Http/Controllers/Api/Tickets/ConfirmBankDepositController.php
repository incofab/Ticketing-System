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

    /** @var PaymentReference $paymentReference */
    $paymentReference = PaymentReference::query()
      ->where('reference', $request->reference)
      ->where('merchant', PaymentMerchantType::BankDeposit)
      ->with('paymentable.eventPackage.event')
      ->firstOrFail();

    $user = currentUser();
    $isStaff = $user->hasRole([RoleType::Manager, RoleType::Admin]);

    abort_unless(
      $isStaff ||
        $paymentReference->paymentable?->eventPackage?->event->user_id ==
          $user->id,
      403,
      'Access denied'
    );

    [$res] = PaymentProcessor::make($paymentReference)->handleCallback();

    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
