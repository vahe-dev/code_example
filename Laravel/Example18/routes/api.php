<?php

use App\Http\Controllers\Admin\GroupsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (){
    Route::middleware('is_admin')->group(function (){
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => "Admin"], function (){

            Route::group(['prefix' => 'groups', 'as' => 'groups.'], function (){
                Route::get('list-by-company/{company}', [GroupsController::class, 'listByCompany'])->name('listByCompany');

                Route::get('/', [GroupsController::class, 'index'])->name('index');
                Route::get('/{group}', [GroupsController::class, 'show'])->name('show');
                Route::post('/', [GroupsController::class, 'store'])->name('store');
                Route::match(['put', 'patch'],'/{group}', [GroupsController::class, 'update'])->name('update');
                Route::delete('/bulkDelete', [GroupsController::class, 'bulkDelete'])->name('bulkDelete');
            });

            Route::group(['prefix' => 'users', 'as' => 'users.'], function (){
                Route::get('/', [UsersController::class, 'index'])->name('index');
            });
        });
    });
});
