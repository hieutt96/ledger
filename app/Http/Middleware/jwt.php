<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\AppException;
use App\Libs\RequestJWT;

class jwt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        if(!$request->jwt) {
            throw new AppException(AppException::ERR_AUTHORIZATION, 'Lỗi xác thực hệ thống');
            
        }
        $data = (array) RequestJWT::decodeJWT($request->jwt); 
        if($data['iss'] != env('WEB_API_KEY')) {
            throw new AppException(AppException::ERR_AUTHORIZATION);
            
        }
        if($data['exp'] < time()) {
            throw new AppException(AppException::REQUEST_EXPIRED);
            
        }
        return $next($request);
    }
}
