<?php

namespace App\Http\Controllers;

use App\McAPICache;
use App\McAPIQueue;
use App\Status;
use Illuminate\Support\Facades\App;

class IndexController extends Controller
{

    public function index()
    {
        return response()->json([
             'documentation'    => 'http://docs.mcapi.de',
             'endpoint'         => 'http://mcapi.de/api'
        ], 200);
    }

    public function apiIndex()
    {

        $status = Status::OK();
        $cacheStatus = (McAPICache::isAvailable() ? Status::OK() : Status::ERROR_INTERAL_SERVICE_UNAVAILABLE());
        $queueStatus = (McAPIQueue::isAvailable() ? Status::OK() : Status::ERROR_INTERAL_SERVICE_UNAVAILABLE());

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
