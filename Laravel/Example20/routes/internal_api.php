<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupsAndReservoirsController;
use App\Http\Controllers\Internal\ReservoirsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'reservoirs'], function () {
    Route::post('/{id}/update-metrics', [ReservoirsController::class, 'updateMetrics'])->name('reservoirs.update-metrics')->where('id', '[0-9]+');
});
