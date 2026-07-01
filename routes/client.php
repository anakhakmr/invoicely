<?php

use App\Livewire\Actions\LogoutClient;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:client')->group(function () {
    Route::livewire('client/login', 'pages::client.login')->name('client.login');
    Route::livewire('client/forgot-password', 'pages::client.forgot-password')->name('client.password.request');
    Route::livewire('client/reset-password/{token}', 'pages::client.reset-password')->name('client.password.reset');
});

Route::middleware('auth:client')->group(function () {
    Route::livewire('client', 'pages::client.dashboard')->name('client.dashboard');
    Route::livewire('client/invoices/{invoice}', 'pages::client.invoice')->name('client.invoices.show');
    Route::livewire('client/invoices/{invoice}/checkout/success', 'pages::client.checkout-success')->name('client.invoices.checkout.success');
    Route::livewire('client/invoices/{invoice}/checkout/cancel', 'pages::client.checkout-cancel')->name('client.invoices.checkout.cancel');
    Route::post('client/logout', LogoutClient::class)->name('client.logout');
});
