<?php

use App\Http\Controllers\MonitorController;
use App\Http\Controllers\MonitorLogEntryController;
use App\Http\Controllers\ProbeResultController;
use App\Http\Middleware\VerifyInternalToken;

require 'api/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::get('monitor/{monitor}/logs', MonitorLogEntryController::class)
        ->where('monitor', '[0-9]+')
        ->name('monitor.logs');
    Route::apiResource('monitor', MonitorController::class);
});

Route::middleware(VerifyInternalToken::class)
    ->post('internal/probe-result', ProbeResultController::class)
    ->name('internal.probe-result');
