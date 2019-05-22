<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Account;
use DB;

class UserController extends Controller
{
    public function getBalance(Request $request) {

    	$request->validate([
    		'user_id' => 'required',
    	]);
    	$account = Account::where('user_id', $request->user_id)->first();
    	if(!$account) {

    		DB::beginTransaction();

    		try{
	    		$acc = new Account;
	    		$acc->user_id = $request->user_id;
	    		$acc->balance = 0;
	    		$acc->stat = 1;
	    		$acc->save();

	    		DB::commit();

	    		$account = $acc;

    		}catch(Exception $e) {

    			DB::rollback();
    		}
    	}
    	return $this->_responseJson([
    		'user_id' => $account->user_id,
    		'balance' => $account->balance,
    	]);
    }

    public function balance(Request $request) {

        $request->validate([
            'user_id' => 'required',
        ]);
        $account = Account::where('user_id', $request->user_id)->first();
        if(!$account) {

            DB::beginTransaction();

            try{
                $acc = new Account;
                $acc->user_id = $request->user_id;
                $acc->balance = 0;
                $acc->stat = 1;
                $acc->save();

                DB::commit();

                $account = $acc;

            }catch(Exception $e) {

                DB::rollback();
            }
        }
        return $this->_responseJson([
            'user_id' => $account->user_id,
            'balance' => $account->balance,
        ]);
    }
}
