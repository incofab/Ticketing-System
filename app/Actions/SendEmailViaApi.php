<?php

namespace App\Actions;

use Http;
use Illuminate\Mail\Mailable;

class SendEmailViaApi
{
  const MAIL_DRIP_URL = 'https://api.maildrip.io/api/v1/emails/transaction';

  public function __construct(private Mailable $mailable)
  {
  }

  public static function run(Mailable $mailable)
  {
    return (new self($mailable))->execute();
  }

  public function execute()
  {
    $body = $this->mailable->render();
    $receiver = $this->mailable->to[0];
    $data = [
      'html' => $body,
      'subject' => $this->mailable->subject,
      'email_address' => $receiver['address'],
      'from' => $this->mailable->from ?? config('app.email'), // $this->mailable->from ?? 'default@email.com',
      'name' => $receiver['name'] ?? 'No name'
    ];
    // info($data);
    Http::withHeader('api-key', config('services.maildrip.api_key'))->post(
      self::MAIL_DRIP_URL,
      $data
    );
  }
}
