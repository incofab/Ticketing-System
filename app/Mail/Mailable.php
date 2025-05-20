<?php

namespace App\Mail;

use App\Actions\SendEmailViaApi;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Mail\Mailable as BaseMailable;

class Mailable extends BaseMailable
{
  function send($mailer)
  {
    // info('mail is being sent now');
    // info($mailer->all)

    SendEmailViaApi::run($this);
    // var_dump($mailer);
    // return parent::send($mailer);
  }

  function queue(Factory $queue)
  {
    // info('Queue called');
    return parent::queue($queue);
  }
}
