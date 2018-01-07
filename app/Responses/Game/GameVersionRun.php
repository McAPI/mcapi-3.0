<?php

namespace App\Responses\Game;

use App\CacheTimes;
use App\Responses\McAPIResponse;
use App\Status;


class GameVersionRun extends McAPIResponse
{

    private static $_ENDPOINT = 'http://s3.amazonaws.com/Minecraft.Download/versions/%s/%s.json';

    private $version;

    public function __construct(string $version)
    {
        parent::__construct(sprintf('game.version.%s:run', $version), [], CacheTimes::GAME_VERSION_RUN(), true);

        $this->version = trim(strtolower($version));
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

        if(empty($this->version)) {
            return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid version.");
        }

        //--- Guzzle client & GET Request Data
        $response = self::guzzle()->get(sprintf(static::$_ENDPOINT, $this->version, $this->version));

        //---
        if(!($force) && $this->serveFromCache()) {
            return $this->getStatus();
        }

        //---
        else if($response->getStatusCode() === Status::OK()) {
            $this->setData(json_decode($response->getBody(), true));
            $this->setStatus(Status::OK());
            $this->save();
            return $this->getStatus();
        }
        else if($response->getStatusCode() === Status::ERROR_CLIENT_FORBIDDEN() || $response->getStatusCode() === Status::ERROR_CLIENT_NOT_FOUND()) {
            $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid or unknown version.");
            $this->save();
            return $this->getStatus();
        }
        //---
        else {
            return $this->setStatus(Status::ERROR_INTERNAL_SERVER_ERROR());
        }

    }

}
