<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\Merchant;
use App\Exceptions\AppException;
use App\Libs\MyUtils;
use App\Account;
use App\Txn;
use App\Libs\Config;
use GuzzleHttp\Client;
use Log;
use \Firebase\JWT\JWT;

class OrderController extends Controller
{
    public function detail(Request $request) {

    	$request->validate([
    		'order_id' => 'required',
    	]);
    	$order = Order::find($request->order_id);
    	if(!$order) {
    		throw new AppException(AppException::ERR_ORDER_EXIST);
    		
    	}
    	$merchant = Merchant::find($order->merchant_id);
    	if(!$merchant) {
    		throw new AppException(AppException::ERR_MERCHANT_NOT_FOUND);
    	}
    	return $this->_responseJson([
    		'id' => $order->id,
    		'user_id' => $order->user_id,
    		'mrc_order_id' => $order->mrc_order_id,
    		'txn_id' => $order->txn_id,
    		'amount' => $order->amount,
    		'url_success' => $order->url_success,
    		'webhooks' => $order->webhooks,
    		'tmn_code' => $merchant->tmn_code,
    		'secret' => $merchant->secret,
    	]);
    }

    public function payment(Request $request) {

    	$request->validate([
    		'email' => 'required',
    		'password' => 'required',
    		'order_id' => 'required',
    	]);

    	$order = Order::find($request->order_id);
    	if(!$order) {

    		throw new AppException(AppException::ERR_ORDER_NOT_EXIST);
    		
    	}
    	if($order->stat == Order::PAY_SUCCESS){

    		throw new AppException(AppException::ERR_ORDER_PAYMENT);
    		
    	}
    	$userId = MyUtils::checkUser($request->email, $request->password);
    	$account = Account::where('user_id', $userId)->first();
    	if($account->balance < $order->amount) {
    		throw new AppException(AppException::ERR_OVER_BALANCE);
    		
    	}
    	$account->balance = $account->balance - $order->amount;
    	$account->save();

    	$txn = new Txn;
    	$txn->user_id = $userId;
    	$txn->account_id = $account->id;
    	$txn->ref_no = 'Thanh toan ma don hang '.$order->id;
    	$txn->type = Config::ORDER_TYPE;
    	$txn->stat = 2;
    	$txn->amount = $order->amount;
    	$txn->save();

    	$order->txn_id = $txn->id;
    	$order->stat = Order::PAY_SUCCESS;
    	$order->ref_no = 'Thanh toan ma don hang '.$order->id;
    	$order->user_id = $userId;
    	$order->save();
    	
    	$dataResponse = [
    		'order_id' => $order->id,
    		'amount' => $order->amount,
    		'user_id' => $userId,
    		'url_success' => $order->url_success,
    		'balance' => $account->balance,
    		'webhooks' => $order->webhooks,
    	];
    	$this->webhook($order, $dataResponse);
    	return $this->_responseJson($dataResponse);
    }

    public function checkBalance($userId, $amount) {

    	$account = Account::where('user_id', $userId)->first();
    	if($account->balance < $amount) {
    		throw new AppException(AppException::ERR_OVER_BALANCE);
    		
    	}
    }

    public function webhook($order, $data) {
    	$privateKey = 
'-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA0MeUORSFVzY5dZOnHwLxN/MU0ae7/bO9oC1trme20CLbfww4
3WktgnCW6/ftLo6WSA5i6xG5LO+7R0Gw3DUP32Y0OUqpVG4EN9onYi1Ll41Xi0XG
Lu3Mb6n5LRwzyhnOPFjjDqtvZTZXsPlLFUHW2fWJxbIZlc8FS9HXrGepvILQeZUa
1m6FAzb4TOGFmT0hm1SOtDMhhkwZtMn/ScooQlu1bAlmakDwx7uJ8gR1B/c7l3Vj
tLMHFbGmr4aWUfa2gdlEgjCw12UfZG6J7gx2zmtopJQY9iJf2IjDgEonXkehOph6
GoGsCdN9pv4vL/zxVhqh2FHRkvOwiYILIrl6eQIDAQABAoIBAG+G/a4UQTqaU810
R6UwTC1IJAnvIWRgXN6xBdNRwf2jcT+IBuR36ACzJlv9P+1L7Amn7b2G5TWVBqUE
+XZYvhbnoOQt9xCMCKLRXZJ7gdL+hRJ3/mtZSokn8lzfoObILDxee0R93e7iPavj
L/G2DxC1spgyEKrPQRQEj8KgSbLR8zIzupMuR7D32N2Wz/wPN9AJOLIpm+Mrf7Tk
tHHLl0NwljbR2CGcxgm65dIqKTiJxaRqQSyS1VstAYopMfR6UtOX9becuekEsjhZ
FlFYVk78Ikn5EKLXtKMRRlXUj7jnanylitxwLLx7XltD+MJrJIjDfPDwr0T7Rlxr
irN54KUCgYEA9AEdhQCq63nZcK0rGKqMnH4fLhVbqcykIsuk64brOD+HsZHnjRNw
Rar1FFd+/hY7prXt8lkzxTGasG+RXbjwF9NmF3kQ3CraB/i7Uu+8ijoz6HlVQhKS
v/5UnEOKvlzbYVFIp4XCCJ/iJOm6WcdDhIzpKKgT7edUaygliWah1cMCgYEA2wsl
sB+vrKRfmbRKxKGI7JrOXpl1H232a5HpggOV22AOHVjzamJ4xp40jQoWO5lXJtPx
gUNduSRA3xNaqkz9S1eydIg7jjpoKIvI3ooVg+KJZ6guwUYsczgWnZK8Kq+S90qa
Z8iUIrQRXmx5oXz3PK+DDYC5jiHf2Cg68SOcHxMCgYAkrUI9qsMGUDOB/1WaCJDI
OSEAsU8s78jAPjIVARu8Qbho1ZCjoQdgQXlDTH+XO8pNnc0df5ELlBA3cx27o7/b
JPiUkKsbHQnW5ulpZwXFFUiWKh7JprcOSvF256QkRxrmvuwX2kA3QakheUx8kDoy
42dsqA8O1JXY4Zj61UWANwKBgQCPKd/+FxeozN43BaGAltt5WUzcg4wLeMGAWSO3
eERv134iLEscEzRBDJHoRNl3JqfRluDXzYHqSgmkQ3AUsrEylyTqCUhzkzUUmxg0
ayfYxS1tdHzqkcTnoZcWchtOAucZfcchYfWAIRThFCEDLTwii8wp/SJKBVXaX6D2
joGxxQKBgQC402fvoZnB+ALTUrWp9trmZOQIKfCxIRPzhv0LGSlSBVejuQXxlyj4
ELH23YggX9o2gfNWz/LbF1yIemaDHF1NG3Oqdtv3XX84Tm5tNhaZDVK3LXsb7UIQ
jvKsPT9ES5ocKsKTJ7GMv1j+q2BMt7i+zz0b+UhTNEIlq0MlU7yQqw==
-----END RSA PRIVATE KEY-----';

$signature = JWT::encode($data, $privateKey, 'RS256');
$data['signature'] = $signature;
		$client = new \GuzzleHttp\Client();
		Log::info($order->webhooks);
		// try {
			$client->request('GET', $order->webhooks, [
					'query' => $data,
			]);

			Log::info(json_encode($data));
		// } catch (Exception $e){
		// 	Log::error("Loi ban webhooks");
		// }
    }
}
