<?php

namespace App\Responses\Game;

use App\CacheTimes;
use App\Responses\McAPIResponse;
use App\Status;
use GuzzleHttp\Exception\ConnectException;
use function GuzzleHttp\Promise\settle;


class GameServiceStatus extends McAPIResponse
{

    public static $_SERVICE_LIST = [
        'minecraft.net',
        'skins.minecraft.net',
        'textures.minecraft.net',
        'account.mojang.com',
        'auth.mojang.com',
        'authserver.mojang.com',
        'sessionserver.mojang.com',
        'api.mojang.com'
    ];

    //---
    private $service;

    public function __construct(string $service = null)
    {

        $this->service = trim(strtolower($service));

        parent::__construct(sprintf('game.service.%s:status', $this->service), [
                'service'   => $this->service,
                'status'    => -1
            ],
            CacheTimes::GAME_SERVICE_STATUS()
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

        if($this->serveFromCache()) {
            return $this->setStatus(Status::OK());
        }

        if(!(in_array($this->service, self::$_SERVICE_LIST))) {
            return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "The service does not exist.");

        }

        try {
            $this->set('status', self::guzzle()->get($this->service)->getStatusCode());
        } catch (ConnectException $e) {
            $this->set('status', -1);
        }

        $this->setStatus(Status::OK());
        $this->save();
        return $this->getStatus();

    }

}
