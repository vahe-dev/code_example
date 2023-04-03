<?php

use App\Http\Controllers\Admin\GroupsAndCompaniesController;

Route::middleware('auth:api')->group(function (){
    Route::middleware('is_admin')->group(function (){
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => "Admin"], function (){
            Route::group(['prefix' => 'groups', 'as' => 'groups.'], function (){
                Route::get('list-by-company/{company}', [GroupsAndCompaniesController::class, 'listByCompany'])->name('listByCompany');
            });
        });
    });
});
