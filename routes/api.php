<?php

use App\Http\Controllers\Api\InvoiceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('invoices', [InvoiceApiController::class, 'index'])->name('api.invoices.index');
    Route::get('invoices/{invoice}', [InvoiceApiController::class, 'show'])->name('api.invoices.show');
    Route::post('invoices/{invoice}/checkout', [InvoiceApiController::class, 'checkout'])->name('api.invoices.checkout');
});
