<?php

use App\Modules\Clinical\Http\Controllers\EncounterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/encounters', [EncounterController::class, 'index']);
    Route::post('/encounters', [EncounterController::class, 'store']);
    Route::get('/encounters/{encounter}', [EncounterController::class, 'show']);
    Route::post('/encounters/{encounter}/close', [EncounterController::class, 'close']);
    Route::post('/encounters/{encounter}/triage', [EncounterController::class, 'saveTriage']);
    Route::post('/encounters/{encounter}/notes', [EncounterController::class, 'addNote']);
    Route::post('/encounters/{encounter}/diagnoses', [EncounterController::class, 'addDiagnosis']);
});
