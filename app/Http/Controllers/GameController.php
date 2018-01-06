<?php

namespace App\Http\Controllers;

use App\Responses\Game\GameVersions;
use App\Responses\Game\ServiceStatus;
use Illuminate\Http\Request;

class GameController extends Controller
{

    public function versions(Request $request)
    {
        $game = new GameVersions();
        $game->fetch($request->all());
        return $game;
    }

    public function servicesStatus(Request $request, string $service = null)
    {
        $status = new ServiceStatus($service);
        $status->fetch($request->all());
        return $status;
    }

}
