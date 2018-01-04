<?php

namespace App\Responses;


use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;

class ServerPing extends McAPIResponse
{

    private $host;
    private $port;
    private $version;

    public function __construct(string $host, string $port, string $version)
    {
        parent::__construct(sprintf('ping.%s.%d', $host, $port), [
                'online'    => false,
                'host'      => null,
                'port'      => -1
            ],
            15
        );

        $this->resolveHostAndPort($host, $port);
        //TODO parse & validate version

    }


    public function fetch(array $request = [], bool $force = false): int
    {

        if($this->getStatus() !== Status::OK()) {
            return $this->getStatus();
        }

        $this->set('host', $this->host);
        $this->set('port', $this->port);

        return $this->setStatus(Status::OK());
    }


    private function resolveHostAndPort(string $host, string $port) : bool
    {

        $this->setStatus(Status::OK());

        //---
        $port = intval($port);

        if($port < 1 || $port > 65535) {
            $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid port.");
            return false;
        }

        $this->port = $port;

        //---
        $host = trim($host);

        if(empty($host)) {
            $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid host.");
            return false;
        }


        //--- Check if it is a valid IP address.
        if(
            filter_var($host, FILTER_VALIDATE_IP, [
                'flags' => [
                    FILTER_FLAG_IPV4,
                    FILTER_FLAG_IPV6,
                    FILTER_FLAG_NO_PRIV_RANGE,
                    FILTER_FLAG_NO_RES_RANGE
                ]
            ])
        ) {
            $this->host = $host;
            return true;
        }

        //---
        $_tmp = gethostbyname($host);
        $validHostname = (!($host === $_tmp));

        if($validHostname) {

            //---
            $components = parse_url($host);
            if($components === false || !(isset($components['path']))) {
                $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid host.");
                return false;
            }

            //---
            $host = $components['path'];

            //---
            $records = dns_get_record(sprintf('_minecraft._tcp.%s', $host, DNS_SRV));

            if(empty($records)) {
                $this->host = $host;
                return true;
            }

            //TODO Well, in theory we could receive multiple SRV records with different priorities and weights.
            // RFC 2782 defines that "[a] client MUST attempt to contact the target host with the lowest-numbered priority [...]".
            // Right now, we sort by the priority AND always (!) pick the first record with the lowest-numbered priority and
            // highest-numbered weight, however, we have (in theory) two issues:
            //
            // 1) We currently DO NOT(!) check if the host is available, in theory, if we have more than one record we could check if
            // another one is available.
            // 2) We currently DO NOT(!) loadbalance the requests as it is suggested in RFC 2782.
            // Yonas - 4.1.2018
            $records = collect($records);
            $records = $records->sortBy(function ($value, $key) {

                $priority   = isset($value['priority']) ? intval($value['priority']) : 0;
                $weight     = isset($value['weight']) ? intval($value['weight']) : 1;

                return ($priority + (1 - (1 / $weight)));

            });

            $record = $records->first();
            if(isset($record['target'])) {
                $this->host = $record['target'];

                if(isset($record['port'])) {
                    $this->port = intval($record['port']);
                }

                return true;
            }

        }
        //
        else {
            $this->setStatus(Status::OK(), "Host unreachable.");
            return false;
        }

        $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid host.");
        return false;

    }

}