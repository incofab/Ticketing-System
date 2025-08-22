<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;
use App\Http\Controllers\Api as Api;
use App\Http\Controllers\Home;
use App\Mail\TicketPurchaseMail;
use App\Models\Ticket;
use App\Models\TicketPayment;

Route::post('/webhook/paystack', [Home\PaymentCallbackController::class, 'paystackWebhook'])->name('webhook.paystack');

Route::get('/callback/airvend', [Home\PaymentCallbackController::class, 'airvendCallback'])->name('callback.airvend');
Route::get('/callback/paystack', [Home\PaymentCallbackController::class, 'paystackCallback'])->name('callback.paystack');
Route::get('/callback/paydestal', [Home\PaymentCallbackController::class, 'paydestalCallback'])->name('callback.paydestal');

Route::get('/dummy1', function () {
  $tps = \App\Models\TicketPayment::query()->whereNull('amount')->with('paymentReference')->take(1000)->get();
  foreach ($tps as $tp) {
    $ref = $tp->paymentReference;
    $tp->update([
      'amount' => $ref?->amount ?? 0,
      'original_amount' => $ref?->amount ?? 0,
      'discount_amount' => 0
    ]);
  }

    
  $refs = \App\Models\PaymentReference::query()
    ->where('status', \App\Enums\PaymentReferenceStatus::Confirmed)
    ->whereBetween('updated_at', [now()->parse('2025-08-20 05:00:00'), now()->parse('2025-08-20 14:00:00')])
    ->where('updated_at', '<', now()->parse('2025-08-20 05:00:00'))
    ->with('paymentable.tickets')
    ->get();
    dd(['count' => $refs->count()]);
    foreach ($refs as $ref) {
      $ticketPayment = $ref->paymentable;
      if($ticketPayment?->email){
        \Mail::to($ticketPayment->email)->send(new \App\Mail\TicketPaymentConfirmationMail($ref));
      }
    }
  return $refs->count() . ' emails sent successfully';
});

Route::get('/login', function () {
    return 'Login page';
})->name('login');

Route::get('/mail-test', function () {
    return new TicketPurchaseMail(Ticket::query()->first());
});

Route::get('/events/{event}/report', function (\App\Models\Event $event) {
    // dd('dksdks');
    return (new \App\Actions\Dummy\GetEventData($event))->run();
});

Route::get('/tickets/{ticket}/print', [Api\Tickets\TicketController::class, 'printTicket'])->name('tickets.print');

