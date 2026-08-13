<?php

use App\Modules\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
});
