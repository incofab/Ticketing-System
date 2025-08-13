<?php

namespace App\Listeners;

use App\Mail\TicketPurchaseMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;

class UpdateTicketSentTime
{
  /**
   * Create the event listener.
   */
  public function __construct()
  {
    //
  }

  /**
   * Handle the event.
   */
  public function handle(MessageSent $event): void
  {
    $mailable = $event->data['mailable'] ?? null;
    if ($mailable instanceof TicketPurchaseMail) {
      $ticket = $mailable->ticket;
      $ticket->update(['sent_at' => now()]);
    }
  }
}
