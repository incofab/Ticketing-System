<?php
namespace App\Support\Payment;

use App\Core\PaystackHelper;
use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;
use Illuminate\Support\Arr;

class PaymentPaystack extends PaymentMerchant
{
  function init(PaymentReferenceDto $paymentReferenceDto)
  {
    $paymentReference = self::createPaymentReference($paymentReferenceDto);

    $ret = (new PaystackHelper())->initialize(
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
    $ret = (new PaystackHelper())->verifyReference(
      $paymentReference->reference
    );
    return $ret;
  }
}
