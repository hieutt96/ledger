<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Account;
use App\Txn;

class StoreController extends Controller
{
    public function minus(Request $request) {

    	$request->validate([
    		'user_id' => 'required',
    		'amount' => 'required',
    	]);
    	$account = Account::where('user_id', $request->user_id)->first();
    	$account->balance = $account->balance - $request->amount;
    	$account->save();

    	$txn = new Txn;
    	$txn->user_id = $request->user_id;
    	$txn->account_id = $account->id;
    	$txn->ref_no = 'Tru tien tu Store';
    	$txn->type = 4;
    	$txn->stat = 2;
    	$txn->amount = $request->amount;
    	$txn->save();
    	return $this->_responseJson([
    		'code' => '00',
    	]);
    }
}
