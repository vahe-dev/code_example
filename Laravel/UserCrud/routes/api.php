<?php

use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupsAndReservoirsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (){
    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => "Admin"], function (){
        Route::group(['prefix' => 'users', 'as' => 'users.'], function (){
            Route::get('/', [UsersController::class, 'index'])->name('index');
            Route::get('/{user}', [UsersController::class, 'show'])->name('show');
            Route::post('/', [UsersController::class, 'store'])->name('store');
            Route::put('/{user}', [UsersController::class, 'update'])->name('update');
            Route::delete('/bulkDelete', [UsersController::class, 'bulkDelete'])->name('bulkDelete');
        });
    });
});
