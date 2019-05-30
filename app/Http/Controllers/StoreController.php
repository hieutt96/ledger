<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Account;
use App\Txn;
use App\Libs\Config;

class StoreController extends Controller
{
    public function minus(Request $request) {

    	$request->validate([
    		'user_id' => 'required',
    		'amount' => 'required',
    	]);
    	
        $account = Account::firstOrCreate(['user_id' => $request->user_id, 'stat' => 1]);
        $account->balance = $account->balance - $request->amount;
        $account->save();

    	$txn = new Txn;
    	$txn->user_id = $request->user_id;
    	$txn->account_id = $account->id;
    	$txn->ref_no = 'Tru tien tu Store';
    	$txn->type = Config::STORE_MINUS;
    	$txn->stat = 2;
    	$txn->amount = $request->amount;
    	$txn->save();
    	return $this->_responseJson([
    		'code' => '00',
    	]);
    }
}
