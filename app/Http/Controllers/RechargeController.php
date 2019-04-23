<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RechargeController extends Controller
{

	const RECHARGE_TYPE_VNPAY = 1;
	const RECHARGE_TYPE_MOMO = 2;

    public function create(Request $request) {

    	$data = json_decode(base64_decode($request->data));
    	dd($data);
    }

    public function processData() {

    	
    }
}
