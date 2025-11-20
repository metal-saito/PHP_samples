<?php

use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('reservations', ReservationController::class)->only([
        'index', 'store', 'destroy'
    ]);
});

