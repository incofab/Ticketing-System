<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;
use App\Http\Controllers\Home;
use App\Mail\TicketPurchaseMail;
use App\Models\Ticket;
use App\Models\TicketPayment;

Route::post('/webhook/paystack', [Home\PaymentCallbackController::class, 'paystackWebhook'])->name('webhook.paystack');

Route::get('/callback/airvend', [Home\PaymentCallbackController::class, 'airvendCallback']);

Route::get('/dummy1', function () {

   $ticketPayments = TicketPayment::select('ticket_payments.*')
      ->join('payment_references', function ($join) {
        $join
          ->on('payment_references.paymentable_id', 'ticket_payments.id')
          ->where(
            'payment_references.paymentable_type',
            'ticket-payment'
          );
      })
      ->where('status', \App\Enums\PaymentReferenceStatus::Confirmed)
      ->withCount('tickets')
      ->get();
    $tps = collect($ticketPayments)->filter(fn($item) => $item->tickets_count < 1);
    foreach ($tps as $tp) {
        $paymentReferences = $tp->paymentReferences;
        foreach ($paymentReferences as $paymentReference) {
            \App\Actions\GenerateTicketFromPayment::generateFromPaymentReference(
              $paymentReference
            );
        }
    }
    return ['Affected ' => $tps->count()];
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

