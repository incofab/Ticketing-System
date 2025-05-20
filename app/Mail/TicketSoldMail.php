<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Send email to the event host when a ticket is sold.
 */
class TicketSoldMail extends Mailable
{
  use Queueable, SerializesModels;
  public Model|TicketPayment $ticketPayment;

  /**
   * Create a new message instance.
   */
  public function __construct(
    public Event $event,
    public PaymentReference $paymentReference
  ) {
    $this->ticketPayment = $paymentReference->paymentable;
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    $amount = number_format($this->paymentReference->amount, 2);
    return new Envelope(
      subject: "Payment of NGN$amount from {$this->ticketPayment?->email}"
    );
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.ticket-sold');
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
