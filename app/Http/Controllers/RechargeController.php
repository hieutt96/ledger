<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Recharge;
use App\Libs\Config;
use App\Txn;
use App\Account;

class RechargeController extends Controller
{

	const RECHARGE_TYPE_VNPAY = 1;
	const RECHARGE_TYPE_MOMO = 2;

    public function create(Request $request) {
    	$request->validate([
    		'recharge_type_id' => 'required',
    		'amount' => 'required|numeric',
    		'url_return' => 'required',
    	]);

    	$user = $request->user;
    	$recharge = new Recharge;
    	$recharge->user_id = $user->id;
    	$recharge->amount = $request->amount;
    	$recharge->type = $request->recharge_type_id;
    	$recharge->stat = Recharge::STAT_PENDING;
    	$recharge->save();

    	$account = Account::firstOrCreate(['user_id' => $user->id, 'stat' => 1]);

    	$txn = new Txn;
    	$txn->user_id = $user->id;
    	$txn->account_id = $account->id;
    	$txn->type = Config::RECHARGE_TYPE;
    	$txn->ref_no = 'mywallet';
    	$txn->save();

    	if($request->recharge_type_id == self::RECHARGE_TYPE_VNPAY) {
    		return $this->_responseJson([
				'recharge_id' => $recharge->id,
				'vnp_url' => $this->createVnpUrl($recharge, $request->url_return),
				'vpn_url_checkout' => Config::VNPAY_CHECKOUT_URL,
    		]);
    	}
    	if($request->recharge_type_id == self::RECHARGE_TYPE_MOMO) {
    		return $this->_responseJson([
    			'recharge_id' => $recharge->id,
				'pay_url' => $this->getPayUrlMoMo($recharge, $request->url_return, $request->url_notify),
    		]);
    	}
    }

    protected function createVnpUrl($recharge, $urlReturn) {
    	
        $vnp_Returnurl = $urlReturn;
		$vnp_TmnCode = Config::VNP_TMN_CODE;
		$vnp_HashSecret = Config::VNP_HASH_SECRET; //Chuỗi bí mật
		$vnp_TxnRef = $recharge->id."";
		$vnp_OrderInfo = 'Ma giao dich '. $recharge->id."";
		$vnp_OrderType = "billpayment";
		$vnp_Amount = $recharge->amount*100;
		$vnp_Locale = 'vn';
		$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
		$inputData = array(
		    "vnp_Version" => "2.0.0",
		    "vnp_TmnCode" => $vnp_TmnCode,
		    "vnp_Amount" => $vnp_Amount,
		    "vnp_Command" => "pay",
		    "vnp_CreateDate" => date('YmdHis'),
		    "vnp_CurrCode" => "VND",
		    "vnp_IpAddr" => $vnp_IpAddr,
		    "vnp_Locale" => $vnp_Locale,   
		    "vnp_OrderInfo" => $vnp_OrderInfo,
		    "vnp_OrderType" => $vnp_OrderType,
		    "vnp_ReturnUrl" => $vnp_Returnurl,
		    "vnp_TxnRef" => $vnp_TxnRef,    
		    'vnp_Merchant' => 'DEMO',
		);
		$out = $inputData;
		ksort($out);
		$query = "";
		$i = 0;
		$hashdata = "";
		foreach ($out as $key => $value) {
		    if ($i == 1) {
		        $hashdata .= '&' . $key . "=" . $value;
		    } else {
		        $hashdata .= $key . "=" . $value;
		        $i = 1;
		    }
		    $query .= urlencode($key) . "=" . urlencode($value) . '&';
		}

		$vnp_Url = "?" . $query;
		if (isset($vnp_HashSecret)) {
		    $vnpSecureHash = md5($vnp_HashSecret . $hashdata);
		    $vnp_Url .= 'vnp_SecureHashType=MD5&vnp_SecureHash=' . $vnpSecureHash;
		}
        return $vnp_Url;
    }

    public function getDataRequestMoMo($recharge, $urlReturn, $urlNotify) {

    	$rechargeId = $recharge->id;
    	$partnerCode = Config::PARTNER_CODE_MOMO;
	    $accessKey = Config::ACCESS_KEY_MOMO;
	    $serectkey = Config::SERECTKEY_MOMO;
	    $orderInfo = "Ma thanh toan dich vu ".$rechargeId;
	    $returnUrl = $urlReturn;
		$notifyurl = $urlNotify;
	    $amount = ''.$recharge->amount.'';
	    $orderid = time()."";
	    $requestId = "".$rechargeId."";
	    $requestType = "captureMoMoWallet";
	    $extraData = "merchantName=Mywallet;merchantId=3948";
	    //before sign HMAC SHA256 signature
	    $rawHash = "partnerCode=".$partnerCode."&accessKey=".$accessKey."&requestId=".$requestId."&amount=".$amount."&orderId=".$orderid."&orderInfo=".$orderInfo."&returnUrl=".$returnUrl."&notifyUrl=".$notifyurl."&extraData=".$extraData;
	    // echo "Raw signature: ".$rawHash."\n";
	    $signature = hash_hmac("sha256", $rawHash, $serectkey);
	    $data =  array(
    		'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderid,
            'orderInfo' => $orderInfo,
            'returnUrl' => $returnUrl,
            'notifyUrl' => $notifyurl,
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        );
	    return json_encode($data);
    }

    public function getPayUrlMoMo($recharge, $urlReturn, $urlNotify) {

    	$dataRequest = $this->getDataRequestMoMo($recharge, $urlReturn, $urlNotify);
    	$url = Config::ENDPOINT_PAYMENT_MOMO;
    	$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataRequest))
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);
        $result = json_decode($result, true);
        return $result['payUrl'];
    }
}
