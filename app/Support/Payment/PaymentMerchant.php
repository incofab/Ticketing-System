<?php
namespace App\Support\Payment;

use App\DTO\PaymentReferenceDto;
use App\Enums\PaymentMerchantType;
use App\Models\PaymentReference;
use App\Support\Res;

abstract class PaymentMerchant
{
  protected string $merchant;

  protected function __construct(string $merchant)
  {
    $this->merchant = $merchant;
  }

  public static function createPaymentReference(
    PaymentReferenceDto $paymentReferenceDto
  ) {
    return PaymentReference::query()->firstOrCreate(
      ['reference' => $paymentReferenceDto->reference],
      $paymentReferenceDto->toArray()
    );
  }

  /**
   * $data Should contain amount|userId|etc
   * @return array{Res, PaymentReference} eg. [$ret, $paymentReference]
   * */
  abstract function init(PaymentReferenceDto $paymentReferenceDto);

  abstract function verify(PaymentReference $paymentReference): Res;

  /**
   * @param string $merchant
   * @return static
   * */
  public static function make(string $merchant)
  {
    switch ($merchant) {
      case PaymentMerchantType::Paystack->value:
      default:
        return new PaymentPaystack($merchant);
    }
  }
}
