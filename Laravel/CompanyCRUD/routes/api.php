<?php

use App\Http\Controllers\Admin\GroupsAndCompaniesController;
use App\Http\Controllers\Admin\ReservoirsController;
use App\Http\Controllers\AuthController;

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (){

    Route::middleware('is_admin')->group(function (){
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => "Admin"], function (){
            Route::group(['prefix' => 'companies', 'as' => 'companies.'], function (){
                Route::get('/companies-with-groups-count', [GroupsAndCompaniesController::class, 'companiesWithGroupsCount'])->name('companiesWithGroupsCount');
                Route::get('/', [GroupsAndCompaniesController::class, 'index'])->name('index');
                Route::get('/{company}', [GroupsAndCompaniesController::class, 'show'])->name('show');
                Route::post('/', [GroupsAndCompaniesController::class, 'store'])->name('store');
                Route::match(['put', 'patch'],'/{company}', [GroupsAndCompaniesController::class, 'update'])->name('update');
                Route::delete('/bulkDelete', [GroupsAndCompaniesController::class, 'bulkDelete'])->name('bulkDelete');
            });
        });
    });
});
