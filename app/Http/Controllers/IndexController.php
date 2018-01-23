<?php

namespace App\Http\Controllers;

use App\Status;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

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
        $cacheStatus = Status::OK();
        $queueStatus = Status::OK();

        //TODO @CLEANUP This deserves some work.
        try {
            Redis::ping();
            $cacheStatus = Status::OK();
        }catch (\Predis\Connection\ConnectionException $exception) {
            $cacheStatus = Status::ERROR_INTERNAL_SERVER_ERROR();
            $status = Status::ERROR_INTERNAL_SERVER_ERROR();
        }

        try {
            Queue::connection()->getPheanstalk()->stats();
            $queueStatus = Status::OK();
        }catch (\Pheanstalk\Exception\ConnectionException $exception) {
            $queueStatus = Status::ERROR_INTERNAL_SERVER_ERROR();
            $status = Status::ERROR_INTERNAL_SERVER_ERROR();
        }

        //--- Response
        return response()->json([
            'status'    => Status::toString($status),
            'mode'      => App::environment(),
            'cache'     => [
                'status'    => Status::toString($cacheStatus),
            ],
            'queue'     => [
                'status'    => Status::toString($queueStatus)
            ]
        ], $status);

    }

}
