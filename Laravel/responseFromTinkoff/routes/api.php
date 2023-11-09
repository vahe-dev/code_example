<?php
use Illuminate\Support\Facades\Route;
// Webhook route for tinkoff
Route::post('/response-from-tinkoff/{key}', 'OrderController@responseFromTinkoff');
