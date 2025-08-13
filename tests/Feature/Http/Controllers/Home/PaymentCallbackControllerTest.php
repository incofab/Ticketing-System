<?php

use Illuminate\Support\Facades\Mail;
use App\Mail\TicketPaymentConfirmationMail;
use App\Models\PaymentReference;

use function Pest\Laravel\getJson;

beforeEach(function () {
  Mail::fake();
  $this->callbackUrl = 'https://example.com/success';
  $this->paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->create([
      'callback_url' => $this->callbackUrl
    ]);

  $url = "https://api.paystack.co/transaction/verify/{$this->paymentReference->reference}";
  Http::fake([
    $url => Http::response(
      [
        'status' => true,
        'data' => [
          'status' => 'success',
          'amount' => ceil($this->paymentReference->amount) * 100
        ]
      ],
      200
    )
  ]);
});

it(
  'redirects and queues email when payment is successful with callback_url',
  function () {
    $reference = $this->paymentReference->reference;
    getJson(
      route('callback.paystack') .
        '?' .
        http_build_query(['reference' => $reference])
    )->assertRedirect();

    Mail::assertQueued(TicketPaymentConfirmationMail::class, function ($mail) {
      return $mail->ticketPayment->is($this->paymentReference->paymentable);
    });
  }
);
