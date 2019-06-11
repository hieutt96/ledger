<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Merchant;
use App\Order;
use App\Exceptions\AppException;
use App\Libs\Config;

class MerchantController extends Controller
{
    public function getUrlPay(Request $request) {
    	$request->validate([
    		'mrc_order_id' => 'required',
    		'amount' => 'required|numeric',
    		'url_success' => 'required',
    		'webhooks' => 'required',
    		'tmn_code' => 'required',
    	]);
    	$order = Order::where('mrc_order_id', $request->mrc_order_id)->first();
    	if($order) {

    		throw new AppException(AppException::ERR_ORDER_EXIST);	
    	}
    	$merchant = Merchant::where('tmn_code', $request->tmn_code)->first();
    	if(!$merchant) {
    		throw new AppException(AppException::ERR_MERCHANT_NOT_FOUND);
    		
    	}
    	$order = new Order;
    	$order->mrc_order_id = $request->mrc_order_id;
    	$order->amount = $request->amount;
    	$order->url_success = $request->url_success;
    	$order->webhooks = $request->webhooks;
    	$order->stat = Order::PAY_PENDING;
    	$order->merchant_id = $merchant->id;
    	$order->save();

    	$checkSum = hash_hmac('SHA1', $order->id, $merchant->secret);
    	return $this->_responseJson([
    		'order_id' => $order->id,
    		'url_pay' => Config::CLIENT_DOMAIN.'/order/payment?order_id='.$order->id.'&checksum='.$checkSum,
    	]);
    }
}
