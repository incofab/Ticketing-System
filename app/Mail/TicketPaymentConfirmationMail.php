<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\TicketPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPaymentConfirmationMail extends Mailable
{
  use Queueable, SerializesModels;

  public TicketPayment $ticketPayment;
  public EventPackage $eventPackage;
  public Event $event;
  public string $callbackUrl;

  /**
   * Create a new message instance.
   */
  public function __construct(public PaymentReference $paymentReference)
  {
    $paymentReference->load('paymentable.eventPackage.event');
    $this->ticketPayment = $paymentReference->paymentable;
    $this->eventPackage = $this->ticketPayment->eventPackage;
    $this->event = $this->eventPackage->event;

    $this->callbackUrl = $this->paymentReference->getCallbackUrl();
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: "{$this->event->title} - Payment Confirmation #{$this->paymentReference->reference}"
    );
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.ticket-payment-confirmation');
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
