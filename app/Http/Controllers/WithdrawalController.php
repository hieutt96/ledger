<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Withdrawal;
use App\Exceptions\AppException;
use App\Txn;
use App\Libs\Config;
use App\Account;
use DB;
use App\Events\WithdrawalSuccess;

class WithdrawalController extends Controller
{

	const WITHDRAWAL_TYPE_VNPAY = 1;
	const WITHDRAWAL_TYPE_MOMO = 2;

    public function create(Request $request) {

    	$request->validate([
    		'withdrawal_type_id' => 'required|numeric',
    		'amount' => 'required|numeric',
    		'url_return' => 'required',
    	]);
    	$user = $request->user;

    	$withdrawal = Withdrawal::create([
    		'user_id' => $user->id,
    		'amount' => $request->amount,
    		'type' => $request->withdrawal_type_id,
    		'stat' => Withdrawal::STAT_PENDING,
    	]);

    	$account = Account::firstOrCreate(['user_id' => $user->id, 'stat' => 1]);
    	Txn::create([
    		'user_id' => $user->id,
    		'account_id' => $account->id,
    		'type' => Config::WITHDRWAL_TYPE,
    		'ref_no' => 'rút tiền',
    		'stat' => Withdrawal::STAT_PENDING,
    	]);
    	if($request->withdrawal_type_id == self::WITHDRAWAL_TYPE_VNPAY) {

    		return $this->_responseJson([
				'withdrawal_id' => $withdrawal->id,
				'vnp_url' => $this->createVnpUrl($withdrawal, $request->url_return),
				'vpn_url_checkout' => Config::VNPAY_CHECKOUT_URL,
    		]);
    	}
    }

    public function createVnpUrl($withdrawal, $urlReturn) {

    	$vnp_Returnurl = $urlReturn;
		$vnp_TmnCode = Config::VNP_TMN_CODE;
		$vnp_HashSecret = Config::VNP_HASH_SECRET; //Chuỗi bí mật
		$vnp_TxnRef = $withdrawal->id."";
		$vnp_OrderInfo = 'Ma giao dich '. $withdrawal->id."";
		$vnp_OrderType = "refund";
		$vnp_Amount = $withdrawal->amount*100;
		$vnp_Locale = 'vn';
		$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
		$inputData = array(
		    "vnp_Version" => "2.0.0",
		    "vnp_TmnCode" => $vnp_TmnCode,
		    "vnp_Amount" => $vnp_Amount,
		    "vnp_Command" => "refund",
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

    public function complete(Request $request) {

        $request->validate([
            'withdrawal_id' => 'required',
        ]);
        $withdrawal = Withdrawal::find($request->withdrawal_id);
        if(!$withdrawal) {
            throw new AppException(AppException::ERR_SYSTEM);
            
        }
        if($withdrawal->type == Config::VNPAY_TYPE) {

            foreach($request->all() as $key => $value) {
                $params[$key] = $value;
            }
            $vnp_SecureHash = $params['vnp_SecureHash'];
            unset($params['vnp_SecureHashType']);
            unset($params['vnp_SecureHash']);
            unset($params['withdrawal_id']);
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
                            $withdrawal->stat = Withdrawal::STAT_SUCCESS;
                            $withdrawal->save();
                            event(new WithdrawalSuccess($withdrawal));
                            $this->createTxnWithdrawal($request->user, Withdrawal::STAT_SUCCESS, $withdrawal->amount);

                            DB::commit();

                            return $this->_responseJson([
                                'code' => '00',
                                'amount' => $withdrawal->amount,
                            ]);
                        }catch(Exception $e) {

                            DB::rollback();
                            throw new AppException(AppException::ERR_SYSTEM);
                        }

                    }catch(Exception $e) {
                        throw new AppException(AppException::ERR_SYSTEM);
                        
                    }
                }else {
                    $withdrawal->stat = Withdrawal::STAT_FAIL;
                    $withdrawal->save();

                    $this->createTxnWithdrawal($request->user, Withdrawal::STAT_FAIL, $withdrawal->amount);
                    return $this->_responseJson([
                        'code' => '01',
                        'message' => 'Giao dịch không thành công',
                    ]);
                }
            }else {
                $withdrawal->stat = Withdrawal::STAT_FAIL;
                $withdrawal->save();

                $this->createTxnWithdrawal($request->user, Withdrawal::STAT_FAIL, $withdrawal->amount);
                return $this->_responseJson([
                    'code' => '01',
                    'message' => 'Giao dịch không thành công, Chữ kí không trùng khớp',
                ]);
                throw new AppException(AppException::ERR_SIGNATURE);
                
            }
            throw new AppException(AppException::ERR_SYSTEM);
        }   
        // if($withdrawal->type == Config::MOMO_TYPE) {
        //     $serectKey = Config::SERECTKEY_MOMO;
        //     $params = [];
        //     foreach($request->all() as $key => $value) {
        //         $params[$key] = $value;
        //     }
        //     $momoSignature = $params['signature'];
        //     $rawHash =  'partnerCode='.$params['partnerCode'].
        //                 '&accessKey='.$params['accessKey'].
        //                 '&requestId='.$params['requestId'].
        //                 '&amount='.$params['amount'].
        //                 '&orderId='.$params['orderId'].
        //                 '&orderInfo='.$params['orderInfo'].
        //                 '&orderType='.$params['orderType'].
        //                 '&transId='.$params['transId'].
        //                 '&message='.$params['message'].
        //                 '&localMessage='.$params['localMessage'].
        //                 '&responseTime='.$params['responseTime'].
        //                 '&errorCode='.$params['errorCode'].
        //                 '&payType='.$params['payType'].
        //                 '&extraData='.$params['extraData'];
        //     $signature = hash_hmac('sha256', $rawHash, $serectKey);
        //     if($request->has('errorCode')) {
        //         $errCode = $request->errorCode;
        //         if($errCode == '0') {
        //             if($momoSignature == $signature) {
        //                 try{
        //                     DB::beginTransaction();

        //                     try {
        //                         $withdrawal->stat = Withdrawal::STAT_SUCCESS;
        //                         $withdrawal->save();
        //                         $this->createTxnWithdrawal($request->user, Withdrawal::STAT_SUCCESS, $withdrawal->amount);

        //                         DB::commit();
        //                     }catch(Exception $e) {

        //                         DB::rollback();
        //                     }

        //                     return $this->_responseJson([
        //                         'code' => '00',
        //                         'amount' => $withdrawal->amount,
        //                     ]);

        //                 }catch(Exception $e) {
        //                     throw new AppException(AppException::ERR_SYSTEM);
                            
        //                 }
        //             }else {
        //                 $withdrawal->stat = Withdrawal::STAT_FAIL;
        //                 $withdrawal->save();

        //                 $this->createTxnWithdrawal($request->user, Withdrawal::STAT_FAIL, $withdrawal->amount);
        //                 return $this->_responseJson([
        //                     'code' => '01',
        //                 ]);
        //             }
        //         }else {
        //             $withdrawal->stat = Withdrawal::STAT_FAIL;
        //             $withdrawal->save();

        //             $this->createTxnWithdrawal($request->user, Withdrawal::STAT_FAIL, $withdrawal->amount);
        //             return $this->_responseJson([
        //                 'code' => '01',
        //             ]);
        //         }
        //     }else {
        //         $withdrawal->stat = Withdrawal::STAT_FAIL;
        //         $withdrawal->save();

        //         $this->createTxnWithdrawal($request->user, Withdrawal::STAT_FAIL, $withdrawal->amount);
        //         return $this->_responseJson([
        //             'code' => '01',
        //         ]);
        //     }
        // }
    }

    public function createTxnWithdrawal($user, $stat, $amount) {

        DB::beginTransaction();
        try {

            $account = Account::firstOrCreate(['user_id' => $user->id, 'stat' => 1]);
            if($stat == Withdrawal::STAT_SUCCESS) {
                if($account->balance > $amount) {
                    $account->balance = $account->balance - $amount;
                    $account->save();
                }else {
                   return $this->_responseJson([
	                    'code' => '02',
	                ]);
                }
            }
            $txn = new Txn;
            $txn->user_id = $user->id;
            $txn->account_id = $account->id;
            $txn->type = Config::WITHDRWAL_TYPE;
            $txn->ref_no = 'rút tiền thành công';
            $txn->stat = $stat;
            $txn->save();

            DB::commit();
        }catch(Exception $e) {
            DB::rollback();
        }
    }
}
