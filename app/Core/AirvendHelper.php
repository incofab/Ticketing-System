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
      'user' => [
        'email' => $email,
        'phone' => '08032485925',
        'name' => 'Unnamed User'
      ],
      'callback_url' => $callbackUrl,
      'customer_transaction_ref' => $reference
    ];

    $res = $this->http()->post($url, $data);

    $link = $res->json('link');

    if (!$link) {
      return failRes(
        'Error: ' . $res->json('message', 'Payment initialization failed'),
        $res->json() ?? []
      );
    }

    return successRes('Payment Initialized', [
      'reference' => $reference,
      'transaction_reference' => $this->extractReferenceFromRedirectUrl($link),
      'redirect_url' => "$link&customer_transaction_ref=$reference"
    ]);
  }
  //http://localhost/callback/airvend?txn_ref=e8183421-6c90-4f7c-905e-fd89fb4f5659&customer_transaction_ref=e8183421-6c90-4f7c-905e-fd89fb4f5659&amount=200.00&status=successful
  function verifyReference($reference): Res
  {
    $url = self::BASE_URL . "payment_process/confirmation/$reference";
    $res = $this->http()->get($url);

    $success = $res->json('success');
    if (!$success || $res->json('payment_status') != 'Successful') {
      return failRes('Transaction NOT successful', ['is_failed' => true]);
    }

    return successRes($res->json('message') ?? 'Transaction verified', [
      'result' => $res->json('message'),
      'amount' => $res->json('amount_received')
    ]);
  }

  function verifyWithCustomerReference($reference): Res
  {
    $url =
      self::BASE_URL . "payment_process/confirmation_by_cutomer_ref/$reference";

    $res = $this->http()->get($url);

    $transactionList = $res->json('txn_list');
    if (empty($transactionList)) {
      return failRes($res->json('message', 'Transaction record not found'), [
        'is_failed' => true
      ]);
    }
    $lastIndex = count($transactionList) - 1;
    $prefix = "txn_list.$lastIndex";

    $success = $res->json('success');
    if (!$success || $res->json("$prefix.payment_status") != 'Successful') {
      return failRes('Transaction NOT successful', ['is_failed' => true]);
    }

    return successRes($res->json("$prefix.message") ?? 'Transaction verified', [
      'result' => $res->json("$prefix.message"),
      'amount' => $res->json("$prefix.amount_received")
    ]);
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
}
