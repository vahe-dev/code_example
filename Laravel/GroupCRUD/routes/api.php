<?php

use App\Http\Controllers\Admin\GroupsAndCompaniesController;
use App\Http\Controllers\Admin\GroupsController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (){
    Route::group(['prefix' => 'groups', 'as' => 'groups.'], function (){
        Route::get('list-by-company/{company}', [GroupsController::class, 'listByCompany'])->name('listByCompany');

        Route::get('/', [GroupsController::class, 'index'])->name('index');
        Route::get('/{group}', [GroupsController::class, 'show'])->name('show');
        Route::post('/', [GroupsController::class, 'store'])->name('store');
        Route::match(['put', 'patch'],'/{group}', [GroupsController::class, 'update'])->name('update');
        Route::delete('/bulkDelete', [GroupsController::class, 'bulkDelete'])->name('bulkDelete');
    });
});
