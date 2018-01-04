<?php

namespace App\Responses;

use App\Extensions\CacheHash;
use App\Status;

class BuycraftDefaultResponse extends McAPIResponse
{

    private static $_BUYCRAFT_ENDPOINT = 'https://plugin.buycraft.net/%s';

    //---
    private $secret;
    private $endpoint;

    private $cache;

    public function __construct(string $secret, string $endpoint, bool $cache, int $cacheTime = -1)
    {

        if ($cache) {
            parent::__construct(sprintf('buycraft.%s.%s', CacheHash::make($secret), $endpoint),
                [],
                $cacheTime
            );
        } else {
            parent::__construct(null, [], $cacheTime);
        }

        $this->secret = $secret;
        $this->endpoint = $endpoint;

        $this->cache = $cache;
    }

    /**
     * @param array $request
     * @param bool $force
     * @return int
     */
    public function fetch(array $request = [], bool $force = false): int
    {

        //---
        if($this->cache && $this->serveFromCache()) {
            return $this->setStatus(Status::OK());
        }

        //---
        $response = self::guzzle()->get(sprintf(self::$_BUYCRAFT_ENDPOINT, $this->endpoint), [
            'headers'   => [
                'X-Buycraft-Secret' => $this->secret
            ]
        ]);

        //--- OK
        if($response->getStatusCode() === Status::OK()) {

            $data = json_decode($response->getBody(), true);

            //NOTE: For some reason Buycraft sometimes puts the relevant data into a "data" JSON Array directly and sometimes not.
            if(is_array($data) && isset($data['data'])) {
                $data = $data['data'];
            }

            $this->setData($data);
            $this->setStatus(Status::OK());

            //--- Cache if caching is enabled
            if ($this->cache) {
                $this->save();
            }

            return $this->getStatus();

        }
        //--- No Server Error
        else if($response->getStatusCode() < 500)  {

            $message = Status::toString($response->getStatusCode());

            //--- Buycraft's more detailed error message.
            $data = json_decode($response->getBody(), true);
            if(is_array($data) && isset($data['error_message'])) {
                $message = $data['error_message'];
            }

            return $this->setStatus(Status::ERROR_CLIENT_FORBIDDEN(), $message);
        }

        return $this->setStatus(Status::ERROR_INTERNAL_SERVER_ERROR(), "Failed to reach Buycraft or received an invalid response.");

    }

}