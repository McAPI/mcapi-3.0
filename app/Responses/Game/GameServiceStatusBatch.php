<?php

namespace App\Responses\Game;

use App\Responses\McAPIResponse;
use App\Status;

use function GuzzleHttp\Promise\settle;


class GameServiceStatusBatch extends McAPIResponse
{

    public function __construct()
    {

        $defaultData = [];
        foreach (GameServiceStatus::$_SERVICE_LIST as $service) {
            $defaultData[] = [
                'service'   => $service,
                'status'    => -1
            ];
        }

        parent::__construct(null, $defaultData);
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

        $currentData = $this->getData();

        $promises = [];
        $promisesIndices = [];
        for($i = 0; $i < count(GameServiceStatus::$_SERVICE_LIST); $i++) {

            $service = GameServiceStatus::$_SERVICE_LIST[$i];
            $current = new GameServiceStatus($service);

            //--- Serve from cache
            if(!($force) && $current->serveFromCache()) {
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

}
