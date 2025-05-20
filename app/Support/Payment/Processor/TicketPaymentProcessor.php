<?php
namespace App\Support\Payment\Processor;

use App\Enums\PaymentReferenceStatus;
use App\Mail\TicketSoldMail;
use App\Models\TicketPayment;
use App\Support\Res;
use DB;
use Mail;

class TicketPaymentProcessor extends PaymentProcessor
{
  function handleCallback(): Res
  {
    if ($this->paymentReference->status !== PaymentReferenceStatus::Pending) {
      return successRes('Payment already completed');
    }

    $res = $this->verify();
    if (!$res->isSuccessful()) {
      if ($res->is_failed) {
        $this->paymentReference
          ->fill(['status' => PaymentReferenceStatus::Cancelled])
          ->save();
      }
      return $res;
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

    if ($event->email) {
      Mail::to($event->email)->queue(
        new TicketSoldMail($event, $this->paymentReference)
      );
    }

    DB::commit();

    return successRes('Payment successful');
  }
}
