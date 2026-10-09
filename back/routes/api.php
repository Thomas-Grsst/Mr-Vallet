<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AnomalyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('agencies', [AgencyController::class, 'index']);
    Route::get('machine-types', [MachineController::class, 'types']);
    Route::get('machines', [MachineController::class, 'index']);
    Route::patch('machines/{machine:ref}/workshop', [MachineController::class, 'updateWorkshop']);
    Route::patch('machines/{machine:ref}/vgp', [MachineController::class, 'updateVgp']);
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::get('reservation-history', [ReservationController::class, 'history']);
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::get('anomalies', AnomalyController::class);
});
