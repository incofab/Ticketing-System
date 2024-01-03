<?php
namespace App\Support\Payment\Processor;

use App\Enums\PaymentReferenceStatus;
use App\Models\TicketPayment;
use App\Support\Res;

class TicketPaymentProcessor extends PaymentProcessor
{
  function handleCallback(): Res
  {
    if ($this->paymentReference->status === PaymentReferenceStatus::Confirmed) {
      return successRes('Payment already completed');
    }

    $this->verify();

    // /** @var User $user */
    // $user = $this->paymentReference->user;
    // /** @var TicketPayment $ticketPayment */
    // $ticketPayment = $this->paymentReference->paymentable;

    $this->paymentReference
      ->fill(['status' => PaymentReferenceStatus::Confirmed])
      ->save();

    return successRes('Payment successful');
  }
}
