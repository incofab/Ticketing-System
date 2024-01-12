<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Support\Payment\Processor\PaymentProcessor;
use Arr;

class HomeController extends Controller
{
  function paystackWebhook()
  {
    info('Paystack webhook called');
    if (
      strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' ||
      !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)
    ) {
      info('paystackWebhook: Method not post or Signature not found');
      return $this->message('Failed: check log for details', 401);
    }
    // Retrieve the request's body
    $input = @file_get_contents('php://input');
    info($input);
    // validate event do all at once to avoid timing attack
    if (!config('app.debug')) {
      if (
        Arr::get($_SERVER, 'HTTP_X_PAYSTACK_SIGNATURE') !==
        hash_hmac('sha512', $input, config('services.paystack.secret-key'))
      ) {
        info('paystackWebhook: Signature validation failed');
        return $this->message('Failed: check log for details', 401);
      }
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
    // $customer = Arr::get($data, 'customer');
    // $email = Arr::get($customer, 'email');
    // $amount = Arr::get($customer, 'amount');
    // $amount = (int) ($data['amount'] / 100);
    // // if (!$email) {
    // //   exit();
    // // }
    $reference = $data['reference'];

    $ret = PaymentProcessor::makeFromReference(
      $reference
    )->handleCallbackWithTransaction();
    // exit(Arr::get($ret, 'message'));
    return $this->ok($ret->toArray());
  }
}
