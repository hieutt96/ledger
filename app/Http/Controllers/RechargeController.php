<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Recharge;
use App\Libs\Config;
use App\Txn;
use App\Account;
use App\Exceptions\AppException;
use DB;
use App\Events\RechargeSuccess;

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
    	$txn->ref_no = 'nạp tiền';
        $txn->stat = Recharge::STAT_PENDING;
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
		    'vnp_Merchant' => time()."",
            'vnp_TransactionNo' => time()."",
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        //execute post
        $result = curl_exec($ch);
        
        //close connection
        curl_close($ch);
        $result = json_decode($result, true);

        return $result['payUrl'];
    }

    public function complete(Request $request) {

        $request->validate([
            'recharge_id' => 'required',
        ]);
        $recharge = Recharge::find($request->recharge_id);
        if(!$recharge) {
            throw new AppException(AppException::ERR_SYSTEM);
            
        }
        if($recharge->type == Config::VNPAY_TYPE) {

            foreach($request->all() as $key => $value) {
                $params[$key] = $value;
            }
            $vnp_SecureHash = $params['vnp_SecureHash'];
            unset($params['vnp_SecureHashType']);
            unset($params['vnp_SecureHash']);
            unset($params['recharge_id']);
            ksort($params);
            $i = 0;
            $hashData = "";
            foreach ($params as $key => $value) {
                if ($i == 1) {
                    $hashData = $hashData . '&' . $key . "=" . $value;
                } else {
                    $hashData = $hashData . $key . "=" . $value;
                    $i = 1;
                }
            }
            $hashSecret = Config::VNP_HASH_SECRET;
            $secureHash = md5($hashSecret . $hashData);
            if($secureHash == $vnp_SecureHash) {
                if($params['vnp_ResponseCode'] == '00') {
                    try{
                        DB::beginTransaction();

                        try {
                            $recharge->stat = Recharge::STAT_SUCCESS;
                            $recharge->save();

                            DB::commit();
                        }catch(Exception $e) {

                            DB::rollback();
                        }

                        $this->createTxnRecharge($request->user, Recharge::STAT_SUCCESS, $recharge->amount);
                        return $this->_responseJson([
                            'code' => '00',
                            'amount' => $recharge->amount,
                        ]);

                    }catch(Exception $e) {
                        throw new AppException(AppException::ERR_SYSTEM);
                        
                    }
                }else {
                    $recharge->stat = Recharge::STAT_FAIL;
                    $recharge->save();

                    $this->createTxnRecharge($request->user, Recharge::STAT_FAIL, $recharge->amount);
                    return $this->_responseJson([
                        'code' => '01',
                    ]);
                }
            }else {
                throw new AppException(AppException::ERR_SIGNATURE);
                
            }
            throw new AppException(AppException::ERR_SYSTEM);
        }   
        if($recharge->type == Config::MOMO_TYPE) {
            $serectKey = Config::SERECTKEY_MOMO;
            $params = [];
            foreach($request->all() as $key => $value) {
                $params[$key] = $value;
            }
            $momoSignature = $params['signature'];
            $rawHash =  'partnerCode='.$params['partnerCode'].
                        '&accessKey='.$params['accessKey'].
                        '&requestId='.$params['requestId'].
                        '&amount='.$params['amount'].
                        '&orderId='.$params['orderId'].
                        '&orderInfo='.$params['orderInfo'].
                        '&orderType='.$params['orderType'].
                        '&transId='.$params['transId'].
                        '&message='.$params['message'].
                        '&localMessage='.$params['localMessage'].
                        '&responseTime='.$params['responseTime'].
                        '&errorCode='.$params['errorCode'].
                        '&payType='.$params['payType'].
                        '&extraData='.$params['extraData'];
            $signature = hash_hmac('sha256', $rawHash, $serectKey);
            if($request->has('errorCode')) {
                $errCode = $request->errorCode;
                if($errCode == '0') {
                    if($momoSignature == $signature) {
                        try{
                            DB::beginTransaction();

                            try {
                                $recharge->stat = Recharge::STAT_SUCCESS;
                                $recharge->save();
                                event(new RechargeSuccess($recharge));
                                $this->createTxnRecharge($request->user, Recharge::STAT_SUCCESS, $recharge->amount);

                                DB::commit();
                            }catch(Exception $e) {

                                DB::rollback();
                            }

                            return $this->_responseJson([
                                'code' => '00',
                                'amount' => $recharge->amount,
                            ]);

                        }catch(Exception $e) {
                            throw new AppException(AppException::ERR_SYSTEM);
                            
                        }
                    }else {
                        $recharge->stat = Recharge::STAT_FAIL;
                        $recharge->save();

                        $this->createTxnRecharge($request->user, Recharge::STAT_FAIL, $recharge->amount);
                        return $this->_responseJson([
                            'code' => '01',
                        ]);
                    }
                }else {
                    $recharge->stat = Recharge::STAT_FAIL;
                    $recharge->save();

                    $this->createTxnRecharge($request->user, Recharge::STAT_FAIL, $recharge->amount);
                    return $this->_responseJson([
                        'code' => '01',
                    ]);
                }
            }else {
                $recharge->stat = Recharge::STAT_FAIL;
                $recharge->save();

                $this->createTxnRecharge($request->user, Recharge::STAT_FAIL, $recharge->amount);
                return $this->_responseJson([
                    'code' => '01',
                ]);
            }
        }
    }

    public function createTxnRecharge($user, $stat, $amount) {

        DB::beginTransaction();
        try {

            $account = Account::firstOrCreate(['user_id' => $user->id, 'stat' => 1]);
            if($stat == Recharge::STAT_SUCCESS) {
                if($account->balance) {
                    $account->balance = $account->balance + $amount;
                    $account->save();
                }else {
                    $account->balance = $amount;
                    $account->save();
                }

            }
            $txn = new Txn;
            $txn->user_id = $user->id;
            $txn->account_id = $account->id;
            $txn->type = Config::RECHARGE_TYPE;
            $txn->ref_no = 'mywallet';
            $txn->stat = $stat;
            $txn->save();

            DB::commit();
        }catch(Exception $e) {
            DB::rollback();
        }
    }
}
