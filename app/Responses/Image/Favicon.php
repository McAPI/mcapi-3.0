<?php

namespace App\Responses;

use App\Status;

class Favicon extends McAPIResponse
{

    private static $_DEFAULT_FAVICON = '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';

    //
    private $ip;
    private $port;
    private $favicon;
    public function __construct(string $ip, string $port)
    {
        parent::__construct(sprintf("image.favicon.%s.%s", $ip, $port), [],-1, false);

        $this->ip = $ip;
        $this->port = $port;
    }

    public function fetch(array $request = [], bool $force = false): int
    {

        $ping = new SocketPing($this->ip, $this->port);
        $ping->fetch([]);

        $favicon = $ping->get('favicon');

        if($ping->getStatus() === Status::OK()) {
            $this->favicon = str_replace('data:image/png;base64,', '', $favicon);
        } else {
            $this->favicon = self::$_DEFAULT_FAVICON;
        }

        return $this->setStatus($ping->getStatus());
    }

    public function toResponse($request)
    {
        $response = base64_decode($this->favicon);

        if($response === false) {
            $response = base64_decode(self::$_DEFAULT_FAVICON);
        }

        return response($response, $this->getStatus())->header('Content-Type', 'image/png');
    }

}