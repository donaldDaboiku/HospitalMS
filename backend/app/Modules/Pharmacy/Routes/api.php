<?php

use App\Modules\Pharmacy\Http\Controllers\PharmacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/products', [PharmacyController::class, 'products']);
    Route::post('/products', [PharmacyController::class, 'storeProduct']);
    Route::post('/products/{product}/receive', [PharmacyController::class, 'receive']);
    Route::get('/prescriptions', [PharmacyController::class, 'prescriptions']);
    Route::post('/prescriptions', [PharmacyController::class, 'prescribe']);
    Route::post('/prescription-items/{prescriptionItem}/dispense', [PharmacyController::class, 'dispense']);
});
