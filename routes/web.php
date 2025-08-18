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
  Mail::to('incofabikenna@gmail.com')->send(new \App\Mail\TicketPurchaseMail(Ticket::query()->first()));
  return 'Mail sent successfully'; 
  // dd(now()->to);
  $ticket = \App\Models\Ticket::query()->first();

    $qr = QrCode::format('png')
      ->size(200)
      ->generate("{$ticket->reference}|{$ticket->ticketPayment->id}");
    $qrCode = 'data:image/png;base64,' . base64_encode($qr);
  return view('tickets.ticket-view-pdf', [
    'ticket' => $ticket,
    'seat' => $ticket->seat,
    'eventPackage' => $ticket->eventPackage,
    'seatSection' => $ticket->eventPackage->seatSection,
    'event' => $ticket->eventPackage->event,
    'qrCode' => $qrCode
  ]);

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

Route::get('/tickets/{ticket}/print', [Api\Tickets\TicketController::class, 'printTicket'])->name('tickets.print');

