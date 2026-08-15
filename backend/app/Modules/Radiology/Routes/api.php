<?php

use App\Modules\Radiology\Http\Controllers\RadiologyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/radiology/orders', [RadiologyController::class, 'orders']);
    Route::post('/radiology/orders', [RadiologyController::class, 'storeOrder']);
    Route::get('/radiology/orders/{radiologyOrder}', [RadiologyController::class, 'showOrder']);
    Route::post('/radiology/orders/{radiologyOrder}/report', [RadiologyController::class, 'saveReport']);
});
