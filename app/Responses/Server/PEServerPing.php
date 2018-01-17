<?php
namespace App\Responses;


use App\CacheTimes;
use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;
use Carbon\Carbon;

class PEServerPing extends ServerResponse
{

    private static $_PING_PACKET = 0x1;
    private static $_MAGIC = '00ffff00fefefefefdfdfdfd12345678';

    public function __construct($host)
    {
        $port = 19132;
        parent::__construct($host, $port, sprintf('server.pe.ping.%s.%d', $host, $port), [
                'online'    => false,
                'host'      => null,
                'port'      => -1,
                'software'  => null,
                'gametype'  => null,
                'motd'      => null,
                'version'   => null,
                'players'   => [
                    'online'    => -1,
                    'max'       => -1,
                    'list'      => []
                ]
            ],
            CacheTimes::SERVER_PE_PING(),
            true
        );
    }

    public function fetch(array $request = [], bool $force = false): int
    {

        if($this->serveFromCache()) {
            return $this->setStatus($this->getStatus());
        }

        //--- NOTE: The Status is != OK when ServerResponse#resolveHostAndPort couldn't resolve the host and port for whatever reason.
        if($this->getStatus() !== Status::OK()) {
            $this->save(Carbon::now()->addMinutes(10)); //TODO Replace time value
            return $this->getStatus();
        }

        $startTime = microtime(true);

        //--- Create
        $socket = null;
        $created = $this->createSocket($socket, SOCK_DGRAM, SOL_UDP);
        if($created !== Status::OK()) {
            return $this->getStatus();
        }

        //--- Connect
        $connected = $this->connectSocket($socket);
        if($connected !== Status::OK()) {
            return $this->getStatus();
        }

        //--- Ping Packet -> Send
        $timeSinceStart = ((microtime(true) - $startTime) * 1000);
        $packet         = pack('cqH*', self::$_PING_PACKET, $timeSinceStart, self::$_MAGIC);
        $pingSend       = @socket_write($socket, $packet, strlen($packet));

        if($pingSend === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "Failed to send the ping packet.");
        }

        //--- Pong Packet <- Receive
        $pongBytesCount = @socket_recv($socket, $response, 16384, MSG_WAITALL);

        //NOTE: packet (1 Byte) + pingid (8 Bytes) + serverid 8 (8 Bytes) + magic (32 Bytes) + Body (32+ Bytes)
        if($pongBytesCount < 81) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The pong packet response is too short.");
        }

        $data = unpack('cpacket/qpingid/qserverid/H32magic/a*body', $response);

        if(count($data) !== 5) {
            socket_close($socket);
            throw new InternalException("The unpacked response has an invalid size.",
                ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
                $this,
                [
                    'data'  => $data
                ]
            );
        }

        $body =  explode(";", $data['body']);

        if(count($body) < 8) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The server didn't respond with enough data.");
        }

        $this->set('online', true);
        $this->set('software', sprintf("%s %s", $body[7], $body[2]));
        $this->set('gametype', $body['6']);
        $this->set('motd', $body['1']);
        $this->set('players.online', intval($body[4]));
        $this->set('players.max', intval($body[5]));
        $this->set('players.list', []);
        $this->set('version', $body[3]);


        socket_close($socket);

        $this->setStatus(Status::OK());
        $this->save();
        return $this->getStatus();
    }
}