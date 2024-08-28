<?php

use App\Http\Controllers\EdtController;
use App\Http\Controllers\ICalendarController;
use Illuminate\Support\Facades\Route;


Route::get('/', [EdtController::class, 'show']);
Route::get('/edt/data', [EdtController::class, 'getData']);

//Route::get('/edt/input', [EdtController::class, 'showInputForm'])->name('edt.input');
//Route::post('/edt/store', [EdtController::class, 'storeEdt'])->name('edt.store');

Route::get('/calendar/feed.ics', [ICalendarController::class, 'feed'])->name('calendar.feed');

