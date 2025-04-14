<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPurchaseMail extends Mailable
{
  use Queueable, SerializesModels;

  public TicketPayment $ticketPayment;
  public Event $event;
  public string $viewTicketUrl;

  /**
   * Create a new message instance.
   */
  public function __construct(public Ticket $ticket)
  {
    // now()->toFormattedDayDateString()
    $this->ticketPayment = $ticket->ticketPayment;
    $this->event = $ticket->eventPackage->event;
    $this->viewTicketUrl =
      "https://shopurban.co/events/{$this->event->id}?" .
      http_build_query(['reference' => $ticket->reference]);
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: "{$this->event->title} - Ticket Purchase #{$this->ticket->id}"
    );
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.ticket-purchase');
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return [];
  }
}
