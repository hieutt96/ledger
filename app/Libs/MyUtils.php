<?php 

namespace App\Libs;
use Auth;
use App\Exceptions\AppException;
use App\Libs\RequestAPI;

class MyUtils {

	public static function Auth($user, $password) {

        $rs = RequestAPI::request('POST', '/api/check-password', [
        	'form_params' => [
        		'email' => $user->email,
        		'password' => $password,
        	],
        ]);
        if($rs->code != AppException::ERR_NONE) {
        	throw new AppException(AppException::ERR_SYSTEM);
        	
        }
	}
}