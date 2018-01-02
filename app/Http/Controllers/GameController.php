<?php

namespace App\Http\Controllers;

use App\Game\GameVersions;
use App\Status;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function versions(Request $request)
    {
        $game = new GameVersions();
        $game->fetch($request);
        return $game;
    }

}
