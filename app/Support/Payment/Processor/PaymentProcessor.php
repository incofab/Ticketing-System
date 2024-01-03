<?php
namespace App\Support\Payment\Processor;

use App\Enums\PaymentReferenceStatus;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use App\Support\Payment\PaymentMerchant;
use App\Support\Res;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class PaymentProcessor
{
  protected PaymentReference $paymentReference;
  protected PaymentMerchant $paymentMerchant;

  protected function __construct(PaymentReference $paymentReference)
  {
    $this->paymentReference = $paymentReference;
    $this->paymentMerchant = PaymentMerchant::make(
      $paymentReference->merchant->value
    );
  }

  protected function verify()
  {
    if ($this->paymentReference->status !== PaymentReferenceStatus::Pending) {
      return throw ValidationException::withMessages([
        'message' => 'Payment Already resolved'
      ]);
    }

    $ret = $this->paymentMerchant->verify($this->paymentReference);

    if (!$ret->isSuccessful()) {
      return throw ValidationException::withMessages([
        'error' => $ret['message']
      ]);
    }

    $amount = $ret['amount'];
    if ($amount < $this->paymentReference->amount) {
      return throw ValidationException::withMessages($ret->toArray());
    }
  }

  abstract function handleCallback(): Res;

  public function handleCallbackWithTransaction()
  {
    DB::beginTransaction();

    $ret = $this->handleCallback();

    if (!$ret->isSuccessful()) {
      DB::rollBack();
    } else {
      DB::commit();
    }

    return $ret;
  }

  public static function makeFromReference(string $reference)
  {
    $paymentRef = PaymentReference::where('reference', $reference)
      ->with(['user'])
      ->firstOrFail();
    return self::make($paymentRef);
  }

  /** @return static */
  public static function make(PaymentReference $paymentReference)
  {
    $className = self::getProcessorClassName(
      $paymentReference->paymentable_type
    );

    return new $className($paymentReference);
  }

  static function getProcessorClassName($paymentableType)
  {
    switch ($paymentableType) {
      case TicketPayment::class:
      case 'ticket-payment':
        return TicketPaymentProcessor::class;
      default:
        throw new Exception("Unknown payment type $paymentableType");
        break;
    }
  }
}
