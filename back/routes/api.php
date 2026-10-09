<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AnomalyController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('agencies', [AgencyController::class, 'index']);
Route::get('machine-types', [MachineController::class, 'types']);
Route::get('machines', [MachineController::class, 'index']);
Route::patch('machines/{machine:ref}/workshop', [MachineController::class, 'updateWorkshop']);
Route::patch('machines/{machine:ref}/vgp', [MachineController::class, 'updateVgp']);
Route::get('reservations', [ReservationController::class, 'index']);
Route::post('reservations', [ReservationController::class, 'store']);
Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
Route::get('anomalies', AnomalyController::class);
