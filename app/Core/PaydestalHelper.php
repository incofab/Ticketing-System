<?php
namespace App\Core;

use App\Support\Res;
use Http;

class PaydestalHelper
{
  const PERCENTAGE_CHARGE = 1.5;
  const FLAT_CHARGE = 100;
  const FLAT_CHARGE_ELIGIBLE = 2500;
  private string $baseUrl = '';

  function __construct()
  {
    $this->baseUrl = config('app.debug')
      ? 'https://devbox.paydestal.com/pay/'
      : 'https://api.paydestal.com/pay/';
  }

  private function http()
  {
    return Http::acceptJson()
      ->baseUrl($this->baseUrl)
      ->contentType('application/json')
      ->withToken(config('services.airvend.secret-key'));
  }

  function initialize($amount, $email, $callbackUrl, $reference = null): Res
  {
    $url = "{$this->baseUrl}api/v1/transaction/initialize";
    $data = [
      'amount' => $amount,
      'reference' => $reference,
      'currency' => 'NGN',
      'callbackUrl' => $callbackUrl,
      'customerEmail' => $email,
      'customerPhone' => null,
      'customerName' => null
    ];

    $res = $this->http()->post($url, $data);
    // info(['paydestal init' => $res->json()]);
    $link = $res->json('link');

    if (!$link) {
      return failRes(
        'Error: ' . $res->json('message', 'Payment initialization failed'),
        $res->json() ?? []
      );
    }

    return successRes('Payment Initialized', [
      'reference' => $reference,
      'redirect_url' => $link
    ]);
  }

  function verifyReference($reference): Res
  {
    $url = "{$this->baseUrl}api/v1/verify-transaction";
    $res = $this->http()->get($url, ['transactionReference' => $reference]);

    $status = $res->json('data.paymentStatus');
    if ($status === 'SUCCESSFUL') {
      return failRes(
        $res->json('responseMessage', 'Transaction NOT successful')
      );
    }

    return successRes($res->json('responseMessage', 'Transaction verified'), [
      'result' => $res->json('data'),
      'amount' => $res->json('data.amount')
    ]);
  }
}
