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
Route::get('/user-balance', 'UserController@getBalance')->name('user.balance')->middleware('authenticate');

Route::get('/balance', 'UserController@balance')->name('balance');

Route::post('/recharge', 'RechargeController@create')->middleware('authenticate')->name('recharge.create');

Route::post('/recharge/complete', 'RechargeController@complete')->middleware('authenticate')->name('recharge.authenticate');

Route::post('/transfer/create', 'TransferController@create')->name('transfer.create')->middleware('authenticate');

Route::post('/withdrawal', 'WithdrawalController@create')->name('withdrawal.create')->middleware('authenticate');

Route::post('/withdrawal/complete', 'WithdrawalController@complete')->middleware('authenticate')->name('withdrawal.complete');

Route::post('/store/minus', 'StoreController@minus')->name('store.minus');

Route::group(['prefix' => '/merchant'], function(){

	Route::post('/payment-url', 'MerchantController@getUrlPay')->name('merchant.url_payment');

});

Route::group(['prefix' => 'order'], function(){

	Route::get('/detail', 'OrderController@detail')->name('order.detail');

	Route::post('/payment', 'OrderController@payment')->name('order.payment');
});

