<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Log;
use App\Exceptions\AppException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        Log::info($exception);
        if($exception instanceof ValidationException) {
            $json = [
                'code' => AppException::ERR_VALIDATION,
                'message' => $exception->validator->getMessageBag(),
                'count' => 0,
                'data' => null,
            ];
            return response()->json($json, 200);
        }
        $exception_class = get_class($exception);

        if(in_array($exception_class, ['InvalidArgumentException', 'OAuthServerException', 'Illuminate\Auth\AuthenticationException'])) {
            $json = [
                'code' => AppException::ERR_INVALID_TOKEN,
                'message' => "Phiên đăng nhập hết hạn",
                'count' => 0,
                'data' => null,
            ];
            return response()->json($json, 200);
        }

        return parent::render($request, $exception);
    }
}
