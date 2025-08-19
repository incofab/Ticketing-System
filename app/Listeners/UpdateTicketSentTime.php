<?php

namespace App\Listeners;

use App\Models\Ticket;
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
    $ticket = $event->data['ticket'] ?? null;

    if ($ticket instanceof Ticket) {
      $ticket->update(['sent_at' => now()]);
    }
  }
}
