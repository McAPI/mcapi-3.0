<?php

namespace App\Http\Controllers;

use App\Status;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Predis\Connection\ConnectionException;

class IndexController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $status = Status::OK();
        $redisStatus = Status::OK();

        //TODO @CLEANUP This deserves some work.
        try {
            Redis::ping();
            $redisStatus = Status::OK();
        }catch (ConnectionException $exception) {
            $redisStatus = Status::ERROR_INTERNAL_SERVER_ERROR();
            $status = Status::ERROR_INTERNAL_SERVER_ERROR();
        }

        //--- Response
        return response()->json([
            'status'    => Status::toString($status),
            'mode'      => App::environment(),
            'cache'     => [
                'status'    => Status::toString($redisStatus),
            ]
        ], $status);

    }

}
