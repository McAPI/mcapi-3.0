<?php

namespace App\Http\Controllers;

use App\Responses\Game\GameVersions;
use Illuminate\Http\Request;

class GameController extends Controller
{

    public function versions(Request $request)
    {
        $game = new GameVersions();
        $game->fetch($request);
        return $game;
    }

}
