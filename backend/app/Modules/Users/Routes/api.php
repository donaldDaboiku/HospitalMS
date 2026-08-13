<?php

use App\Modules\Users\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('users', UserController::class);
});
