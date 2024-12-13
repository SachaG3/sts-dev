<?php

use App\Http\Controllers\EdtController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ICalendarController;
use Illuminate\Support\Facades\Route;


Route::get('/', [EdtController::class, 'show']);
Route::get('/edt/data', [EdtController::class, 'getData']);
Route::get('/edt/remaining-weeks', [EdtController::class, 'getRemainingWeeks']);


//Route::get('/edt/input', [EdtController::class, 'showInputForm'])->name('edt.input');
//Route::post('/edt/store', [EdtController::class, 'storeEdt'])->name('edt.store');
Route::get('/home', [HomeController::class, 'show'])->name('home');
Route::get('/check_authentication', [HomeController::class, 'checkAuthentication'])->name('check_authentication');

Route::get('/edt/{id}/edit', [EdtController::class, 'edit'])->name('cours.edit');
Route::put('/edt/{id}', [EdtController::class, 'update'])->name('cours.update');
Route::get('/calendar/feed.ics', [ICalendarController::class, 'feed'])->name('calendar.feed');
Route::get('/calendar/formation', [ICalendarController::class, 'feedWithoutAlternance']);


Route::post('/verify-password', [HomeController::class, 'verifyPassword'])->name('verify_password');
Route::get('/get-emails', [HomeController::class, 'getEmails'])->name('get_emails');

Route::post('/add-email', [HomeController::class, 'addEmail'])->name('add_email');


Route::get('/get-assignments', [HomeController::class, 'getAssignments'])->name('get_assignments');
Route::post('/add-assignment', [HomeController::class, 'addAssignment'])->name('add_assignment');
Route::get('/get-weeks', [HomeController::class, 'getAvailableWeeks'])->name('get_weeks');


Route::post('/extend-assignment/{id}', [HomeController::class, 'extendAssignment'])->name('extend_assignment');


//Route::get('/education', [MatiereController::class, 'index'])->name('education.index');
//Route::post('/education/matiere', [MatiereController::class, 'storeMatiere'])->name('education.storeMatiere');
//Route::post('/education/prof', [MatiereController::class, 'storeProf'])->name('education.storeProf');
//Route::post('/education/matiere-prof', [MatiereController::class, 'storeMatiereProf'])->name('education.storeMatiereProf');
Route::get('/get-matieres', [HomeController::class, 'getMatieres'])->name('get_matieres');


Route::post('/extend-assignment/{id}', [HomeController::class, 'extendAssignment'])->name('extend_assignment');
