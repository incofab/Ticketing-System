<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use URL;

class TicketPurchaseMail extends Mailable
{
  use Queueable, SerializesModels;

  public TicketPayment $ticketPayment;
  public Event $event;
  public string $viewTicketUrl;
  public $qrCode;

  /**
   * Create a new message instance.
   */
  public function __construct(public Ticket $ticket)
  {
    $this->ticketPayment = $ticket->ticketPayment;
    $this->event = $ticket->eventPackage->event;
    $this->viewTicketUrl =
      "https://shopurban.co/events/{$this->event->id}?" .
      http_build_query(['reference' => $ticket->reference]);

    $this->qrCode =
      '<img src="data:image/svg+xml;base64,' .
      base64_encode($this->ticket->qr_code) .
      '">'; // $ticket->qr_code;
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
    return [
        // Attachment::fromData(
        //   fn() => $this->getPdf(),
        //   "ticket-{$this->ticket->id}.pdf"
        // )->withMime('application/pdf')
      ];
  }

  private function getPdf()
  {
    $url = URL::temporarySignedRoute('tickets.print', now()->addMinutes(30), [
      'ticket' => $this->ticket->id
    ]);
    $pdfGenUrl = config('services.pdf-gen-url') . "?url={$url}";
    $pdfContent = Http::get($pdfGenUrl)->body();
    return $pdfContent;
  }
}
