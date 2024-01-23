<?php
namespace App\Support\Payment;

use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;

class PaymentBankDeposit extends PaymentMerchant
{
  function init(PaymentReferenceDto $paymentReferenceDto)
  {
    $paymentReference = self::createPaymentReference($paymentReferenceDto);

    $ret['amount'] = $paymentReferenceDto->amount;
    return [
      successRes('', [
        'amount' => $paymentReference->amount,
        'reference' => $paymentReference->reference
      ]),
      $paymentReference
    ];
  }

  function verify(PaymentReference $paymentReference): Res
  {
    return successRes('', ['amount' => $paymentReference->amount]);
  }
}
