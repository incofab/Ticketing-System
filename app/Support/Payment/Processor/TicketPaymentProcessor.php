<?php
namespace App\Support\Payment\Processor;

use App\Enums\PaymentMerchantType;
use App\Enums\PaymentReferenceStatus;
use App\Mail\TicketPaymentConfirmationMail;
use App\Mail\TicketSoldMail;
use App\Models\TicketPayment;
use DB;
use Mail;

class TicketPaymentProcessor extends PaymentProcessor
{
  /**
   * @retuurn array{Res, PaymentReference}
   */
  function handleCallback()
  {
    if ($this->paymentReference->status !== PaymentReferenceStatus::Pending) {
      return [successRes('Payment already completed'), $this->paymentReference];
    }

    if ($this->paymentReference->amount > 0) {
      $res = $this->verify();
      if (!$res->isSuccessful()) {
        $canCancel =
          now()->diffInMinutes($this->paymentReference->created_at, true) > 20;
        if ($canCancel) {
          //$res->is_failed) {
          $this->paymentReference
            ->fill(['status' => PaymentReferenceStatus::Cancelled])
            ->save();
        }
        return [$res, $this->paymentReference];
      }
    }

    // /** @var User $user */
    // $user = $this->paymentReference->user;
    /** @var TicketPayment $ticketPayment */
    $ticketPayment = $this->paymentReference->paymentable;
    $eventPackage = $ticketPayment->eventPackage;
    $event = $eventPackage->event;

    DB::beginTransaction();
    $this->paymentReference
      ->fill(['status' => PaymentReferenceStatus::Confirmed])
      ->save();

    $eventPackage
      ->fill([
        'quantity_sold' =>
          $eventPackage->quantity_sold + $ticketPayment->quantity
      ])
      ->save();

    if (
      $ticketPayment?->email &&
      in_array($this->paymentReference->merchant, [
        PaymentMerchantType::Paystack,
        PaymentMerchantType::Paydestal,
        PaymentMerchantType::Airvend
      ])
    ) {
      Mail::to($ticketPayment->email)->queue(
        new TicketPaymentConfirmationMail($this->paymentReference)
      );
    }

    if ($event->email) {
      Mail::to($event->email)->queue(
        new TicketSoldMail($event, $this->paymentReference)
      );
    }

    $coupon = $ticketPayment->coupon;
    if ($coupon) {
      $coupon
        ->fill([
          'usage_count' => $coupon->usage_count + $ticketPayment->quantity
        ])
        ->save();
    }

    DB::commit();

    return [successRes('Payment successful'), $this->paymentReference];
  }
}
