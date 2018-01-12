<?php

namespace App\Responses;


use App\CacheTimes;
use App\Status;
use Carbon\Carbon;

class ServerPing extends ServerResponse
{

    private $version;

    public function __construct(string $host, string $port, string $version)
    {
        parent::__construct($host, $port, sprintf('ping.%s.%d', $host, $port), [
                'online'    => false,
                'host'      => null,
                'port'      => -1
            ],
            CacheTimes::SERVER_PING()
        );

        //TODO parse & validate version

    }


    public function fetch(array $request = [], bool $force = false): int
    {

        //--- Check if resolveHostAndPort failed
        if($this->getStatus() !== Status::OK()) {
            $this->save(Carbon::now()->addMinutes(10));
            return $this->getStatus();
        }

        $this->set('host', $this->getHost());
        $this->set('port', $this->getPort());

        return $this->setStatus(Status::OK());
    }


}