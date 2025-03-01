<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Events;
use App\Http\Controllers\Api\Seats;
use App\Http\Controllers\Api\Tickets;
use App\Http\Controllers\Api\Payments;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Events\EventCategoryController;
use App\Http\Controllers\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [Auth\ApiAuthController::class, 'login'])->name('login');
Route::post('/register', [Auth\ApiAuthController::class, 'register'])->name('register');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/logout', [Auth\ApiAuthController::class, 'logout'])->name('logout');
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {
    Route::get('/dashboard', [Admin\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/events/{event}/dashboard', [Admin\AdminController::class, 'eventDashboard'])->name('admin.event.dashboard');
});

Route::group(['prefix' => 'seats'], function () {
    Route::get('/index/{seatSection?}', [Seats\SeatController::class, 'index'])->name('seats.index');
    Route::get('/available/{eventPackage}', [Seats\SeatController::class, 'available'])->name('seats.available');
    Route::post('/{seatSection}', [Seats\SeatController::class, 'store'])->name('seats.store');
});

Route::group(['prefix' => 'seat-sections'], function () {
    Route::get('/index', [Seats\SeatSectionController::class, 'index'])->name('seat-sections.index');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/{seatSection}/update', [Seats\SeatSectionController::class, 'update'])->name('seat-sections.update');
    });
});

Route::apiResource('event-categories', EventCategoryController::class)->middleware('auth:sanctum')->except('show');

Route::group(['prefix' => 'event-seasons'], function () {
    Route::get('/index/{eventCategory?}', [Events\EventSeasonController::class, 'index'])->name('event-seasons.index');
    Route::get('/upcoming/{eventCategory?}', [Events\EventSeasonController::class, 'upcomingSeason'])->name('event-seasons.upcoming');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/event-category/{eventCategory}/store', [Events\EventSeasonController::class, 'store'])->name('event-seasons.store');
        Route::post('/{eventSeason}/update', [Events\EventSeasonController::class, 'update'])->name('event-seasons.update');
        Route::post('/{eventSeason}/destroy', [Events\EventSeasonController::class, 'destroy'])->name('event-seasons.destroy');
    });
});

Route::group(['prefix' => 'events'], function () {
    Route::get('/index/{eventSeason?}', [Events\EventController::class, 'index'])->name('events.index');
    Route::get('/upcoming/{eventSeason?}', [Events\EventController::class, 'upcomingEvents'])->name('events.upcoming');
    Route::get('/show/{event}', [Events\EventController::class, 'show'])->name('events.show');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/event-seasons/{eventSeason}/store', [Events\EventController::class, 'store'])->name('events.store');
        Route::post('/{event}/update', [Events\EventController::class, 'update'])->name('events.update');
        Route::post('/{event}/destroy', [Events\EventController::class, 'destroy'])->name('events.destroy');
    });
});

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'event-image'], function () {
    Route::post('/store', [Events\EventImageController::class, 'store'])->name('event-images.store');
    Route::post('/{eventImage}/destroy', [Events\EventImageController::class, 'destroy'])->name('event-images.destroy');
});

Route::group(['prefix' => 'event-packages'], function () {
    Route::get('/index', [Events\EventPackageController::class, 'index'])->name('event-packages.index');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/events/{event}/store', [Events\EventPackageController::class, 'store'])->name('event-packages.store');
        Route::post('/{eventPackage}/destroy', [Events\EventPackageController::class, 'destroy'])->name('event-packages.destroy');
    });
});

Route::group(['prefix' => 'tickets'], function () {
    Route::post('/init-payment/{eventPackage}', Tickets\InitTicketPurchaseController::class)->name('tickets.init-payment');
    Route::post('/confirm-payment', Tickets\ConfirmPaymentController::class)->name('tickets.confirm-payment');
    Route::post('/generate-ticket', Tickets\GenerateTicketController::class)->name('tickets.generate');
    Route::post('/{ticket}/event-attendees/create', Tickets\EventAttendeeController::class)->name('tickets.event-attendees.store');
    Route::get('/retrieve', Tickets\RetrieveTicketController::class)->name('tickets.retrieve');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/verify', Tickets\VerifyTicketController::class)->name('tickets.verify');
        Route::post('/bank-deposit/confirm', Tickets\ConfirmBankDepositController::class)->name('tickets.bank-deposit.confirm');
    });
    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::get('/index', Tickets\ListTicketController::class)->name('tickets.index');
    });
});

Route::group(['prefix' => 'payments'], function () {
    Route::get('/index', [Payments\PaymentController::class, 'index'])->name('payments.index');
});
