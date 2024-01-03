<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;
use App\Http\Controllers\Api\Events;
use App\Http\Controllers\Api\Tickets;
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
    Route::post('/init-payment', Tickets\InitTicketPurchaseController::class)->name('tickets.init-payment');
    Route::post('/confirm-payment', Tickets\ConfirmPaymentController::class)->name('tickets.confirm-payment');
    Route::post('/generate-ticket', Tickets\GenerateTicketController::class)->name('tickets.generate');
});
