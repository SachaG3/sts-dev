<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::post('/publiee', [ScheduleController::class, 'fetchAndStore']);
