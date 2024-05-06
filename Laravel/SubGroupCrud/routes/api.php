<?php

use App\Http\Controllers\Admin\SubGroupsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupsAndReservoirsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (){

    Route::middleware('is_admin')->group(function (){
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => "Admin"], function (){

            Route::group(['prefix' => 'sub-groups', 'as' => 'sub-groups.'], function (){
                Route::get('list-by-group/{group}', [SubGroupsController::class, 'listByGroup'])->name('listByGroup');
                Route::get('/', [SubGroupsController::class, 'index'])->name('index');
                Route::get('/{group}', [SubGroupsController::class, 'show'])->name('show');
                Route::post('/', [SubGroupsController::class, 'store'])->name('store');
                Route::match(['put', 'patch'],'/{group}', [SubGroupsController::class, 'update'])->name('update');
                Route::delete('/bulkDelete', [SubGroupsController::class, 'bulkDelete'])->name('bulkDelete');
            });
        });
    });
});
