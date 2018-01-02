<?php

namespace App\Http\Controllers;

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
        $redisStatus = 'Unknown';

        try {
            Redis::ping();
            $redisStatus = 'Ok';
        }catch (ConnectionException $exception) {
            $redisStatus = 'Error';
        }

        return response()->json([
            'status'    => "Ok",
            'mode'      => App::environment(),
            'cache'     => [
                'status'    => $redisStatus,
            ]
        ]);

        //dd(Redis::info()['Server']);
    }

}
