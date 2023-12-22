<?php
use Illuminate\Support\Facades\Route;

// webhook route for preorders from CRM system
Route::post('/request-from-crm-pre-order/key', 'StoreController@requestFromCrmPreOrder');