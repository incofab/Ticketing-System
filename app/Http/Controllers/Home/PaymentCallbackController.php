<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Support\Payment\Processor\PaymentProcessor;
use Arr;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
  function paystackWebhook()
  {
    // info('Paystack webhook called');
    if (
      strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' ||
      !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)
    ) {
      info('paystackWebhook: Method not post or Signature not found');
      return $this->message('Failed: check log for details', 401);
    }
    // Retrieve the request's body
    $input = @file_get_contents('php://input');
    // info($input);
    // validate event do all at once to avoid timing attack
    if (
      Arr::get($_SERVER, 'HTTP_X_PAYSTACK_SIGNATURE') !==
      hash_hmac('sha512', $input, config('services.paystack.secret-key'))
    ) {
      info('paystackWebhook: Signature validation failed');
      return $this->message('Failed: check log for details', 401);
    }

    // http_response_code(200);
    // parse event (which is json string) as object
    // Do something - that will not take long - with $event
    $event = json_decode($input, true) ?? request()->all();
    //         dlog('Paystack webhook below');
    //         dlog($event);

    if (Arr::get($event, 'event') != 'charge.success') {
      return $this->message('Failed: Invalid event', 401);
    }

    $data = Arr::get($event, 'data');

    if (Arr::get($data, 'status') != 'success') {
      return $this->message('Failed: Invalid event', 401);
    }

    $reference = $data['reference'];
    return $this->verifyReference($reference, false);
  }

  function airvendCallback(Request $request)
  {
    return $this->verifyReference($request->txn_ref, true);
  }

  function paystackCallback(Request $request)
  {
    return $this->verifyReference($request->reference, true);
  }

  function paydestalCallback(Request $request)
  {
    return $this->verifyReference($request->reference, true);
  }

  private function verifyReference($reference, $canRedirect = true)
  {
    [$ret, $paymentReference] = PaymentProcessor::makeFromReference(
      $reference
    )->handleCallback();

    if (!$paymentReference->callback_url || !$canRedirect) {
      return $ret->isSuccessful()
        ? $this->ok($ret->toArray())
        : $this->message($ret->getMessage() ?? 'Payment not successful', 403);
    }
    // $ticketPayment = $paymentReference->paymentable;
    // if ($ticketPayment?->email) {
    //   Mail::to($ticketPayment->email)->queue(
    //     new TicketPaymentConfirmationMail($paymentReference)
    //   );
    // }
    return redirect($paymentReference->getCallbackUrl());
  }
}
