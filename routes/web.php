<?php

use App\Enums\PaymentReferenceStatus;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;
use App\Http\Controllers\Home;
use App\Mail\TicketPurchaseMail;
use App\Models\EventPackage;
use App\Models\Ticket;
use App\Models\TicketPayment;

Route::post('/webhook/paystack', [Home\PaymentCallbackController::class, 'paystackWebhook'])->name('webhook.paystack');

Route::get('/callback/airvend', [Home\PaymentCallbackController::class, 'airvendCallback']);

Route::get('/dummy1', function () {
    $tickets = Ticket::query()->where('event_id', -1)->with('ticketPayment')->get();
    foreach ($tickets as $key => $ticket) {
        $email = $ticket->ticketPayment->email;
        if(!$email){
            continue;
        }
        Mail::to($email)->queue(
            new TicketPurchaseMail($ticket)
          );
    }
    dd("Done for ". $tickets->count() . ' tickets');
    // return (new TicketPurchaseMail($ticket));
    dd('dksds');
    $eventPackages = EventPackage::query()->with('ticketPayments')->get();
    /** @var EventPackage $eventPackage */
    foreach ($eventPackages as $key => $eventPackage) {
        $quantity = TicketPayment::query()->select('ticket_payments.*')
            ->join('payment_references', function ($q) {
                $q->on('payment_references.paymentable_id', 'ticket_payments.id')
                ->where('payment_references.paymentable_type', 'ticket-payment');
            })
            ->where('payment_references.status', PaymentReferenceStatus::Confirmed)
            ->where('ticket_payments.event_package_id', $eventPackage->id)
            ->sum('ticket_payments.quantity');
            
        dd([
            'Tickets count' => Ticket::query()->where('event_package_id', $eventPackage->id)->count(),
            'collection quantity' => $quantity,
            'eventPackage ticketPayments count' => $eventPackage->ticketPayments->sum('quantity'),
            'eventPackage quantity' => $eventPackage->quantity_sold
        ]);
        $eventPackage->fill(['quantity_sold' => $quantity])->save();
    }
    dd('Done for '.$eventPackages->count().' package(s)');
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

