<?php

use App\Modules\Laboratory\Http\Controllers\LaboratoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/lab/tests', [LaboratoryController::class, 'tests']);
    Route::post('/lab/tests', [LaboratoryController::class, 'storeTest']);
    Route::get('/lab/orders', [LaboratoryController::class, 'orders']);
    Route::post('/lab/orders', [LaboratoryController::class, 'storeOrder']);
    Route::get('/lab/orders/{labOrder}', [LaboratoryController::class, 'showOrder']);
    Route::post('/lab/orders/{labOrder}/collect', [LaboratoryController::class, 'collect']);
    Route::post('/lab/order-items/{labOrderItem}/results', [LaboratoryController::class, 'enterResult']);
    Route::post('/lab/results/{labResult}/verify', [LaboratoryController::class, 'verifyResult']);
});
