<?php
namespace App\Core;

use App\Support\Res;
use Http;

class AirvendHelper
{
  const PERCENTAGE_CHARGE = 1.5;
  const FLAT_CHARGE = 100;
  const FLAT_CHARGE_ELIGIBLE = 2500;
  const BASE_URL = 'https://airgate.ng/api2checkout/';

  private function http()
  {
    return Http::acceptJson()
      ->contentType('application/json')
      ->withHeader('merchant-key', config('services.airvend.merchant-key'))
      ->withHeader('secret-key', config('services.airvend.secret-key'))
      ->withHeader('mode', config('app.debug') ? 'sandbox' : 'live');
  }

  function initialize($amount, $email, $callbackUrl, $reference = null): Res
  {
    $url = self::BASE_URL . 'payment_initiate_hosted';

    $data = [
      'amount' => $amount,
      'user' => ['email' => $email],
      'callback_url' => $callbackUrl,
      'reference' => $reference
    ];

    $res = $this->http()->post($url, $data);

    $link = $res->json('link');

    if (!$link) {
      return failRes(
        'Error: ' . $res->json('message', 'Paystack initialization failed'),
        $res->json() ?? []
      );
    }

    return successRes('Payment Initialized', [
      'reference' => $reference,
      'transaction_reference' => $this->extractReferenceFromRedirectUrl($link),
      'redirect_url' => $link
    ]);
  }
  //http://localhost/callback/airvend?txn_ref=e8183421-6c90-4f7c-905e-fd89fb4f5659&customer_transaction_ref=e8183421-6c90-4f7c-905e-fd89fb4f5659&amount=200.00&status=successful
  function verifyReference($reference): Res
  {
    $url = self::BASE_URL . "payment_process/confirmation/$reference";
    $res = $this->http()->get($url);

    $success = $res->json('success');
    if (!$success) {
      return failRes('Transaction NOT successful');
    }

    return successRes(
      $res->json('response.ResponseDescription') ?? 'Transaction verified',
      [
        'result' => $res->json('response'),
        'amount' => $res->json('response.Amount') / 100
      ]
    );
  }

  function verifyWithCustomerReference($reference): Res
  {
    $url =
      self::BASE_URL . "payment_process/confirmation_by_cutomer_ref/$reference";

    $res = $this->http()->get($url);

    $transactionList = $res->json('txn_list');
    $lastIndex = count($transactionList) - 1;
    $prefix = "txn_list.$lastIndex";

    $success = $res->json('success');
    if (!$success || !$res->json("$prefix.success")) {
      return failRes('Transaction NOT successful');
    }

    return successRes(
      $res->json("$prefix.response.ResponseDescription") ??
        'Transaction verified',
      [
        'result' => $res->json("$prefix.response"),
        'amount' => $res->json("$prefix.response.Amount") / 100
      ]
    );
  }

  function extractReferenceFromRedirectUrl(string $url): ?string
  {
    $parsedUrl = parse_url($url);
    if (empty($parsedUrl['path'])) {
      return null;
    }
    $path = $parsedUrl['path'];
    $pathParts = explode('/', $path);
    $lastPart = end($pathParts);

    return $lastPart;
  }

  private function addCharge($amount)
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

  private function removeCharge($chargedAmount)
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
    $addCharge = $this->addCharge($enteredAmount);
    $removeCharge = $this->removeCharge($addCharge);
    $i++;
    $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 3000;
    $addCharge = $this->addCharge($enteredAmount);
    $removeCharge = $this->removeCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 5500;
    $addCharge = $this->addCharge($enteredAmount);
    $removeCharge = $this->removeCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 800;
    $addCharge = $this->addCharge($enteredAmount);
    $removeCharge = $this->removeCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    $enteredAmount = 'dsk';
    $addCharge = $this->addCharge($enteredAmount);
    $removeCharge = $this->removeCharge($addCharge);
    $i++;
    $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
    $str .= '<br /><br />';

    dd($str);
  }
}
