<?php

use App\Http\Controllers\MonitorController;

require 'api/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('monitor', MonitorController::class);
});
