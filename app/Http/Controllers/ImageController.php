<?php

namespace App\Http\Controllers;

use App\Responses\Favicon;
use Illuminate\Http\Request;

class ImageController extends Controller
{

    public function favicon(Request $request, string $ip, string $port = '25565')
    {
        $ping = new Favicon($ip, $port);
        $ping->fetch($request->all());
        return $ping;
    }

}
