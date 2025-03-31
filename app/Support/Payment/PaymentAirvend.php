<?php
namespace App\Support\Payment;

use App\Core\AirvendHelper;
use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;
use Illuminate\Support\Arr;

class PaymentAirvend extends PaymentMerchant
{
  function init(PaymentReferenceDto $paymentReferenceDto)
  {
    $paymentReference = self::createPaymentReference($paymentReferenceDto);

    $ret = (new AirvendHelper())->initialize(
      $paymentReference->amount,
      Arr::get(
        $paymentReference->user,
        'email',
        $paymentReferenceDto->getEmail() ?? config('app.email')
      ),
      $paymentReferenceDto->getCallbackUrl(),
      $paymentReference->reference
    );

    $ret['amount'] = $paymentReferenceDto->amount;
    return [$ret, $paymentReference];
  }

  function verify(PaymentReference $paymentReference): Res
  {
    $ret = (new AirvendHelper())->verifyWithCustomerReference(
      $paymentReference->reference
    );
    return $ret;
  }
}
