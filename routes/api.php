<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/recharge', 'RechargeController@create')->middleware('authenticate')->name('recharge.create');

Route::post('/recharge/complete', 'RechargeController@complete')->middleware('authenticate')->name('recharge.authenticate');

