<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::view('/login', 'welcome')->name('login');
Route::get('/pricing', [BillingController::class, 'pricing']);

Route::middleware('auth')->group(function () {
    // Current user for the front end: only what the front end shows.
    Route::get('/me', fn (Request $request) => $request->user()->only(['id', 'name', 'email']));

    Route::post('/billing/checkout', [BillingController::class, 'checkout']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/export', [ReportController::class, 'export'])->middleware('pro');
    Route::get('/reports/forecast', [ReportController::class, 'forecast'])->middleware('pro');
});
