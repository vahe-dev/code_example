<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

use App\Http\Controllers\UsersController;

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::group(['middleware' => ['auth:api', 'check_app_version']], function () {
        Route::group(['middleware' => ['active.user']], function () {
            Route::post('users/uploadAvatar', [UsersController::class, 'uploadAvatar'])->name('users.uploadAvatar');
        });
    });
});
