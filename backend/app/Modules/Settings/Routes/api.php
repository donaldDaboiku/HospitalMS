<?php

use App\Modules\Settings\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index']);
});
