<?php

use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ProbeResultController;
use App\Http\Middleware\VerifyInternalToken;

require 'api/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('monitor', MonitorController::class);
});

Route::middleware(VerifyInternalToken::class)
    ->post('internal/probe-result', ProbeResultController::class)
    ->name('internal.probe-result');
