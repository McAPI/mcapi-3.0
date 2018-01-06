<?php

namespace App\Responses\Game;

use App\CacheTimes;
use App\Responses\McAPIResponse;
use App\Status;


class GameVersions extends McAPIResponse
{

    private static $_ENDPOINT = 'https://launchermeta.mojang.com/mc/game/version_manifest.json';

    public function __construct()
    {
        parent::__construct('game.versions', [
            'latest'    => null,
            'versions'  => []
            ],
            CacheTimes::GAME_VERSIONS()
        );
    }

    /**
     * This method fetches data.
     *
     * @param array $request
     * @param bool $force
     * @return int
     */
    public function fetch(array $request = [], bool $force = false): int
    {

        //--- Guzzle client & GET Request Data
        $client = self::guzzle();
        $response = $client->get(static::$_ENDPOINT);

        //---
        if($this->serveFromCache()) {
            return $this->setStatus(Status::OK());
        }
        //---
        else if($response->getStatusCode() === Status::OK()) {

            $extracted = json_decode($response->getBody(), true);

            $this->set('latest', $extracted['latest']);
            $this->set('versions', $extracted['versions']);

            //--- Set cache
            $this->save();

            return $this->setStatus(Status::OK());
        }
        //---
        else {
            return $this->setStatus(Status::ERROR_INTERNAL_SERVER_ERROR());
        }

    }

}
