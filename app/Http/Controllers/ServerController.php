<?php

namespace App\Http\Controllers;

use App\Responses\ServerPing;
use Illuminate\Http\Request;

class ServerController extends Controller
{

    public function ping(Request $request, string $ip, string $port = '25565', string $version = '1.12.2')
    {
        //TODO Automatically set $version to latest version
        $ping = new ServerPing($ip, $port, $version);
        $ping->fetch($request->all());
        return $ping;
    }

}
