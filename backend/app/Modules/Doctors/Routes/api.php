<?php

use App\Modules\Doctors\Http\Controllers\DoctorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::post('/doctors/profiles', [DoctorController::class, 'upsertProfile']);
    Route::get('/doctors/{doctorUserId}/schedules', [DoctorController::class, 'schedules']);
    Route::post('/doctors/schedules', [DoctorController::class, 'storeSchedule']);
});
