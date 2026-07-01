<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

require __DIR__.'/settings.php';
require __DIR__.'/client.php';
