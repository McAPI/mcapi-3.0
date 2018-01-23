<?php

namespace App\Http\Controllers;

use App\Responses\PESocketPing;
use App\Responses\SocketPing;
use App\Responses\SocketQuery;
use Illuminate\Http\Request;

class ServerController extends Controller
{

    public function ping(Request $request, string $ip, string $port = '25565')
    {
        $ping = new SocketPing($ip, $port);
        $ping->fetch($request->all());
        return $ping;
    }

    public function query(Request $request, string $ip, string $port = '25565')
    {
        $query = new SocketQuery($ip, $port);
        $query->fetch($request->all());
        return $query;
    }

    public function pePing(Request $request, string $ip)
    {
        $ping = new PESocketPing($ip);
        $ping->fetch($request->all());
        return $ping;
    }

}
