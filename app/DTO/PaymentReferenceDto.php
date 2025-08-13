<?php
namespace App\DTO;

use App\Enums\PaymentReferenceStatus;
use App\Models\PaymentReference;
use Illuminate\Database\Eloquent\Model;

class PaymentReferenceDto
{
  function __construct(
    public string $merchant,
    private Model $paymentable,
    public int|float $amount,
    public string $reference,
    public ?string $callback_url = '',
    public $user_id = null,
    public array $extraData = []
  ) {
    $this->reference =
      $reference ?? PaymentReference::generateReferece($merchant);
  }

  function setReference(string $reference)
  {
    $this->reference = $reference;
  }

  function getPaymentable()
  {
    return $this->paymentable;
  }

  function getEmail()
  {
    return $this->paymentable->email;
  }

  function getExtraData()
  {
    return $this->extraData;
  }

  function toArray()
  {
    return [
      'merchant' => $this->merchant,
      'paymentable_id' => $this->paymentable->id,
      'paymentable_type' => $this->paymentable->getMorphClass(),
      'amount' => $this->amount,
      'user_id' => $this->user_id,
      'reference' => $this->reference,
      'status' => PaymentReferenceStatus::Pending,
      'content' => $this->getExtraData(),
      'callback_url' => $this->callback_url
    ];
  }
}
