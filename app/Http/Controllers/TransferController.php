<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\RequestAPI;
use App\Exceptions\AppException;
use App\Libs\MyUtils;
use DB;
use App\Transfer;
use App\Txn;
use App\Libs\Config;
use App\Account;
use Cookie;

class TransferController extends Controller
{
    public function create(Request $request) {

    	$request->validate([
    		'amount' => 'required|numeric',
    		'email' => 'required',
    		'description' => 'required',
    		'password' => 'required',
    	]);

    	$fromUser = $request->user;

    	$response = RequestAPI::request('POST', '/api/check-user-exists', [
    		'headers' => ['Authorization' => $request->header('Authorization')],
    		'form_params' => [
    			'email' => $request->email,
    		],
    	]);
    	if($response->code != AppException::ERR_NONE) {

    		throw new AppException(AppException::ERR_SYSTEM);
    		
    	}
    	$toUser = $response->data;
    	$rs = RequestAPI::requestSetting('POST', '/api/txn/caculate-fee', [
    		'headers' => ['Authorization' => $request->header('Authorization')],
    		'form_params' => [
    			'amount' => $request->amount,
    		],
    	]);
    	if($rs->code != AppException::ERR_NONE) {
    		throw new AppException(AppException::ERR_SYSTEM);
    		
    	}
    	$fee = $rs->data->fee;
		$this->checkLimitTxn($fromUser, $request->amount, $fee, Cookie::get('access_token'));

    	MyUtils::Auth($fromUser, $request->password);

    	$this->createTransaction($fromUser, $toUser, $request->amount, $fee, $request->description);

    	return $this->_responseJson([
    		'email' => $request->email,
            'amount' => $request->amount,
            'fee' => $fee,
            'description' => $request->description,
    	]);
    }

    public function checkLimitTxn($fromUser, $amount, $fee, $accessToken) {

    	$account = Account::where('user_id', $fromUser->id)->first();
    	if(!$account || ($account->balance < $amount + $fee)) {
    		throw new AppException(AppException::ERR_OVER_BALANCE);
    		
    	}

    	$rs = RequestAPI::requestSetting('POST', '/api/txn/check-limit', [
    		'headers' => ['Authorization' => 'Bearer '.$accessToken],
    	]);
    	if($rs->code != AppException::ERR_NONE) {
    		throw new AppException(AppException::ERR_SYSTEM);
    		
    	}
    	if($amount > $rs->data->limit_txn_up) {
    		throw new AppException(AppException::ERR_OVER_TXN);
    		
    	}
    	if($amount < $rs->data->limit_txn_down) {
    		throw new AppException(AppException::ERR_DOWN_TXN);
    		
    	}
    }

    public function createTransaction($fromUser, $toUser, $amount, $fee, $description) {

    	DB::beginTransaction();

    	try{
    		$transfer = new Transfer;
    		$transfer->from_user_id = $fromUser->id;
    		$transfer->to_user_id = $toUser->user_id;
    		$transfer->amount = $amount;
    		$transfer->fee = $fee;
    		$transfer->description = $description;
    		$transfer->save();


    		$accountFrom = Account::firstOrCreate(['user_id' => $fromUser->id, 'stat' => 1]);
    		$accountFrom->balance = $accountFrom->balance - $amount- $fee;
    		$accountFrom->save();

    		$txnFrom = new Txn;
    		$txnFrom->user_id = $fromUser->id;
    		$txnFrom->account_id = $accountFrom->id;
    		$txnFrom->ref_no = 'Chuyển tiền';
    		$txnFrom->type = Config::TRANSFER_TYPE;
    		$txnFrom->stat = 2;
    		$txnFrom->save();

    		$accountTo = Account::firstOrCreate(['user_id' => $toUser->user_id, 'stat' => 1]);
    		$accountTo->balance = $accountTo->balance + $amount;
    		$accountTo->save();
    		
    		$txnFrom = new Txn;
    		$txnFrom->user_id = $toUser->user_id;
    		$txnFrom->account_id = $accountTo->id;
    		$txnFrom->ref_no = 'Nhận tiền chuyển';
    		$txnFrom->type = Config::TRANSFER_TYPE;
    		$txnFrom->stat = 2;
    		$txnFrom->save();

    		DB::commit();
    	}catch(Exception $e) {

    		DB::rollback();

    		return $this->_responseJson([
	    		'code' => '01',
	    	]);
    	}
    }
}
