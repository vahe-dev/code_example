<?php

use App\Http\Controllers\ReservoirMetricsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (){
    Route::group(['prefix' => 'reservoirs', 'as' => 'reservoirs.'], function () {
        Route::get('/{reservoirId}/export-metrics', [ReservoirMetricsController::class, 'exportMetrics'])->name('exportMetrics.index')->where('reservoirId', '[0-9]+');
    });
});
