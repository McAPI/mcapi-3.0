<?php

namespace App\Responses\Game;

use App\CacheTimes;
use App\Responses\McAPIResponse;
use App\Status;
use GuzzleHttp\Exception\ConnectException;
use function GuzzleHttp\Promise\settle;


class GameServiceStatus extends McAPIResponse
{

    private static $_SERVICE_LIST = [
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
    private $checkAll = false;

    public function __construct(string $service = null)
    {

        $this->service = trim(strtolower($service));
        $this->checkAll = empty($service);

        //---
        if($this->checkAll) {
            $defaultData = [];
            foreach (self::$_SERVICE_LIST as $service) {
                $defaultData[] = [
                    'service'   => $service,
                    'status'    => -1
                ];
            }

            parent::__construct(null, $defaultData);
        }
        //---
        else {
            parent::__construct(sprintf('game.service.%s:status', $this->service), [
                    'service'   => $this->service,
                    'status'    => -1
                ],
                CacheTimes::GAME_SERVICE_STATUS()
            );
        }
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

        //--- NOTE: We are NOT serving checkAll from cache but rather fetch all statuses individual from their own cache entries,
        // so we cannot serve the checkAll request from cache.
        if($this->checkAll === false && $this->serveFromCache()) {
            return $this->setStatus(Status::OK());
        }

        if($this->checkAll === true) {

            $currentData = $this->getData();

            $promises = [];
            $promisesIndices = [];
            for($i = 0; $i < count(self::$_SERVICE_LIST); $i++) {

                $service = self::$_SERVICE_LIST[$i];
                $current = new GameServiceStatus($service);

                //--- Serve from cache
                if($current->isCached() && $current->serveFromCache()) {
                    $currentData[$i]['status'] = $current->get('status');
                }
                //--- or request
                else {
                    $promises[$service] = self::guzzle()->getAsync($service);
                    $promisesIndices[$service] = $i;
                }

            }

            //--- Wait & Process
            $results = settle($promises)->wait();
            foreach ($results as $service => $data) {

                //NOTE Only successfully send requests have a 'value' (Response) entry.
                $status = -1;
                if(array_key_exists('value', $data)) {
                    $status = $data['value']->getStatusCode();
                }
                $currentData[$promisesIndices[$service]]['status'] = $status;

                //--- Cache
                $service = new GameServiceStatus($service);
                $service->set('status', $status);
                $service->save();

            }

            $this->setData($currentData);

            return $this->setStatus(Status::OK());

        }
        //
        else {

            if(in_array($this->service, self::$_SERVICE_LIST)) {

                try {
                    $this->set('status', self::guzzle()->get($this->service)->getStatusCode());
                } catch (ConnectException $e) {
                    $this->set('status', -1);
                }

                $this->setStatus(Status::OK());
                $this->save();
                return $this->getStatus();
            }
            //
            else {
                return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "The service does not exist.");
            }

        }

    }

}
