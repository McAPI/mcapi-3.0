<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        //TODO Own log system or use third-party?
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {


        if(!App::environment('local')) {
            $trace = encrypt(json_encode($e->getTrace()));
            if ($e instanceof InternalException) {
                return response()->json([
                    'exception' => 'InternalException',
                    'message' => $e->getMessage(),
                    'debugMessage' => $e->getDebugMessage(),
                    'code' => $e->getCode(),
                    'origin' => $e->getResponse(),
                    'additional' => $e->getAdditional(),
                    'trace' => $trace,
                ], 500);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'exception' => 'NotFoundHttpException',
                    'trace' => $trace
                ], 404);
            }
        }

        return parent::render($request, $e);
    }
}
