<?php
namespace App\Support\Payment;

use App\Core\PaydestalHelper;
use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;
use Illuminate\Support\Arr;

class PaymentPaydestal extends PaymentMerchant
{
  function init(PaymentReferenceDto $paymentReferenceDto)
  {
    $paymentReference = self::createPaymentReference($paymentReferenceDto);

    $ret = (new PaydestalHelper())->initialize(
      $paymentReference->amount,
      Arr::get(
        $paymentReference->user,
        'email',
        $paymentReferenceDto->getEmail() ?? config('app.email')
      ),
      route('callback.paydestal'),
      $paymentReference->reference
    );

    $ret['amount'] = $paymentReferenceDto->amount;
    return [$ret, $paymentReference];
  }

  function verify(PaymentReference $paymentReference): Res
  {
    $ret = (new PaydestalHelper())->verifyReference(
      $paymentReference->reference
    );
    return $ret;
  }
}
