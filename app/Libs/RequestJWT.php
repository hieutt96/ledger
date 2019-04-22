<?php 

namespace App\Libs;

use Firebase\JWT\JWT;
use App\Exceptions\AppException;

class RequestJWT {

	const ENCODE_ALG = 'HS256';


	public static function decodeJWT($jwt) {
		$secret = env('WEB_API_SECRET', 'auQszsTMdamHJK8GUAsg');
		try {

			$data = JWT::decode($jwt, $secret, [self::ENCODE_ALG]);

		}catch(SignatureInvalidException $e) {
			throw new AppException(AppException::ERR_AUTHORIZATION, 'Lỗi giải mã jwt');
			
		}

		return $data;
	}
}