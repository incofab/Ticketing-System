<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;
use App\Models\EventPackage;
use App\Models\TicketPayment;

Route::get('/dummy1', function () {
    $eventPackages = EventPackage::query()->with('ticketPayments')->get();
    /** @var EventPackage $eventPackage */
    foreach ($eventPackages as $key => $eventPackage) {
        $queryQuantity = TicketPayment::query()->where('event_package_id', $eventPackage->id)->sum('quantity');
        $quantity = $eventPackage->ticketPayments->sum('quantity');
        dd([
            'query quantity' => $queryQuantity,
            'collection quantity' => $quantity
        ]);
        $eventPackage->fill(['quantity_sold' => $quantity])->save();
    }
    dd('No packages');
});
Route::get('/login', function () {
    return 'Login page';
})->name('login');
