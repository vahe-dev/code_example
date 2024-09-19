<?php

use App\Models\Redirect;

Auth::routes();

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function() {
    Route::post('/homepage/addTopBanner', 'Admin\HomePageController@addTopBanner');
    Route::get('/homepage/getTopBannerContent', 'Admin\HomePageController@getTopBannerContent')->name('top_banner_content.data');
    Route::get('/homepage/removeTopBanner/{id}', 'Admin\HomePageController@removeTopBanner');
    Route::get('/topBannerEditModal/{id}', 'Admin\HomePageController@topBannerEditModal');
    Route::post('/updateTopBanner/{id}', 'Admin\HomePageController@updateTopBanner');
});
