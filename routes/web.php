<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as Web;

Route::get('/dummy1', function () {
    dd(',ksdmksdmds = ');
});
Route::get('/login', function () {
    return 'Login page';
})->name('login');
