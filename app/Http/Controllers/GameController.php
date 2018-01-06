<?php

namespace App\Http\Controllers;

use App\Responses\Game\GameVersionRun;
use App\Responses\Game\GameVersions;
use App\Responses\Game\GameServiceStatus;
use Illuminate\Http\Request;

class GameController extends Controller
{

    public function versions(Request $request)
    {
        $game = new GameVersions();
        $game->fetch($request->all());
        return $game;
    }

    public function versionRun(Request $request, string $version)
    {
        $versionRun = new GameVersionRun($version);
        $versionRun->fetch($request->all());
        return $versionRun;
    }

        public function servicesStatus(Request $request, string $service = null)
    {
        $status = new GameServiceStatus($service);
        $status->fetch($request->all());
        return $status;
    }

}
