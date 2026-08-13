<?php

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
});
