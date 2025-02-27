<?php
namespace App\Core;

use App\Support\Res;
use Http;

class PaystackHelper
{
  const PERCENTAGE_CHARGE = 1.5;
  const FLAT_CHARGE = 100;
  const FLAT_CHARGE_ELIGIBLE = 2500;

  function initialize($amount, $email, $callbackUrl, $reference = null): Res
  {
    $url = 'https://api.paystack.co/transaction/initialize';
    // Add paystack charge
    $amount = $this->addPaystackCharge($amount);
    // Change to kobo
    $amount = $amount * 100;

    $subAccount = config('services.paystack.ticket-subaccount');
    $data = [
      'amount' => $amount,
      'email' => $email,
      'callback_url' => $callbackUrl,
      'reference' => $reference,
      ...$subAccount
        ? ['subaccount' => $subAccount, 'bearer' => 'subaccount']
        : []
    ];

    $res = Http::acceptJson()
      ->withToken(config('services.paystack.secret-key'))
      ->post($url, $data);

    if (!$res->json('status') || !$res->json('data.authorization_url')) {
      return failRes(
        'Error: ' . $res->json('message', 'Paystack initialization failed'),
        $res->json() ?? []
      );
    }

    return successRes('Payment Initialized', [
      'reference' => $res->json('data.reference'),
      'redirect_url' => $res->json('data.authorization_url')
    ]);
  }

  //abandoned
  //success
  //
  function verifyReference($reference): Res
  {
    $url = 'https://api.paystack.co/transaction/verify/' . $reference;

    $res = Http::acceptJson()
      ->withToken(config('services.paystack.secret-key'))
      ->get($url);

    $status = $res->json('data.status');
    if (!$res->json('status') || $status !== 'success') {
      return failRes(
        $res->json('data.gateway_response', 'Transaction NOT successful'),
        [
          'result' => $res->json('data'),
          'is_failed' => in_array($status, ['abandoned', 'failed', 'reversed'])
        ]
      );
    }

    // Getting here means payment was successful
    $amount = (int) ($res->json('data.amount') / 100);
    if ($amount < 1) {
      return failRes('Invalid amount');
    }

    return successRes('Reference verified', [
      'result' => $res->json('data'),
      'status' => $status,
      'amount' => $amount
    ]);
  }

  function addPaystackCharge($amount)
  {
    $amount = (int) $amount;
    if (empty($amount)) {
      return 0;
    }

    $finalAmount = $amount;

    if ($amount >= self::FLAT_CHARGE_ELIGIBLE) {
      $finalAmount = $amount + self::FLAT_CHARGE;
    }

    return ceil($finalAmount / (1 - self::PERCENTAGE_CHARGE / 100));
  }

  function removePaystackCharge($chargedAmount)
  {
    $chargedAmount = (int) $chargedAmount;
    if (empty($chargedAmount)) {
      return 0;
    }

    $amount = floor($chargedAmount * (1 - self::PERCENTAGE_CHARGE / 100));

    if ($amount >= self::FLAT_CHARGE_ELIGIBLE) {
      $amount = $amount - self::FLAT_CHARGE;
    }

    return $amount;
  }

  function testPaystackCharges()
  {
    $str = '';
    $i = 0;
    $enteredAmount = 2000;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 3000;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 5500;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 800;
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 'dsk';
    $addCharge = $this->addPaystackCharge($enteredAmount);
    $removeCharge = $this->removePaystackCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    dd($str);
  }
}
