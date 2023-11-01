<?php

use App\Http\Controllers\Admin\ReservoirsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupsAndReservoirsController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function (){
    Route::middleware('is_admin')->group(function (){
            Route::group(['prefix' => 'reservoirs', 'as' => 'reservoirs.'], function (){
                Route::get('/', [ReservoirsController::class, 'index'])->name('index');
            });
    });
});
