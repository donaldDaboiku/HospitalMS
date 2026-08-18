<?php

use App\Modules\Billing\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/invoices', [BillingController::class, 'invoices']);
    Route::post('/invoices', [BillingController::class, 'storeInvoice']);
    Route::get('/invoices/{invoice}', [BillingController::class, 'showInvoice']);
    Route::post('/invoices/{invoice}/issue', [BillingController::class, 'issueInvoice']);
    Route::post('/invoices/{invoice}/payments', [BillingController::class, 'recordPayment']);
    Route::post('/invoices/{invoice}/claims', [BillingController::class, 'submitClaim']);
    Route::get('/insurance-providers', [BillingController::class, 'insuranceProviders']);
    Route::post('/insurance-providers', [BillingController::class, 'storeInsuranceProvider']);
});
