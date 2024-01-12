<?php

use App\Enums\PaymentReferenceStatus;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;
use App\Http\Controllers\Home;
use App\Models\EventPackage;
use App\Models\Ticket;
use App\Models\TicketPayment;

Route::get('/dummy1', function () {
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

Route::post('/webhook/paystack', [Home\HomeController::class, 'paystackWebhook'])->name('webhook.paystack');
