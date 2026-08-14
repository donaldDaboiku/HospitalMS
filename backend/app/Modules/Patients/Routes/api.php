<?php

use App\Modules\Patients\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients/duplicates', [PatientController::class, 'duplicates']);
    Route::post('/patients/family', [PatientController::class, 'storeFamily']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{patient}', [PatientController::class, 'show']);
    Route::put('/patients/{patient}', [PatientController::class, 'update']);
    Route::post('/patients/{patient}/photo', [PatientController::class, 'uploadPhoto']);
    Route::get('/patients/{patient}/photo', [PatientController::class, 'photo']);
});
