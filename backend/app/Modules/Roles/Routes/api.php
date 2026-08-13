<?php

use App\Modules\Roles\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
});
