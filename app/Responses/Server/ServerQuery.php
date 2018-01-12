<?php

namespace App\Responses;


use App\CacheTimes;
use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;
use Carbon\Carbon;

class ServerQuery extends ServerResponse
{

    private static $_MAGIC_1   = 0xFE;
    private static $_MAGIC_2   = 0xFD;

    private static $_FULL_STAT = 0x00;
    private static $_HANDSHAKE = 0x09;

    public function __construct(string $host, string $port)
    {
        parent::__construct($host, $port, sprintf('server.query.%s.%d', $host, $port), [
                'online'    => false,
                'host'      => null,
                'port'      => -1,
                'software'  => null,
                'gametype'  => null,
                'game_id'   => null,
                'motd'      => null,
                'version'   => null,
                'map'       => null,
                'players'   => [
                    'online'    => -1,
                    'max'       => -1,
                    'list'      => []
                ]
            ],
            CacheTimes::SERVER_QUERY(),
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
            return $this->getStatus();
        }

        $socket = @socket_create($this->getIpType(), SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 0));

        if($socket === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERNAL_SERVER_ERROR(), sprintf('Failed to create a socket. (%s)', socket_strerror(socket_last_error())));
        }

        socket_set_nonblock($socket);
        $connected = false;
        $now = microtime(true);
        $timeout = 2;
        do {
            socket_clear_error($socket);
            $connected  = @socket_connect($socket, $this->getHost(), $this->getPort());
            $error      = socket_last_error($socket);
            $elapsed    = (microtime(true) - $now) * 1000;
        } while (($error === SOCKET_EINPROGRESS || $error === SOCKET_EALREADY) && $elapsed < $timeout);
        socket_set_block($socket);

        if($connected === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERNAL_SERVER_ERROR(), sprintf('Failed to initiate a connection. (%s)', socket_strerror(socket_last_error($socket))));
        }

        // Session ID
        $sessionId = rand(0, 15); //@TODO Why does the protocol fail (?) when we send a number that takes more than one byte?


        //--- Handshake -> Send
        $handshakePacket = pack('c3N1', self::$_MAGIC_1, self::$_MAGIC_2, self::$_HANDSHAKE, $sessionId);
        $handshakeSend   = @socket_send($socket, $handshakePacket, strlen($handshakePacket), MSG_EOR);

        if($handshakeSend === false) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), 'Failed to send the handshake packet.');
        }

        //--- Handshake <- Receive
        //    NOTE: 14 => 1 Byte + 4 Bytes + 8 Bytes (allows up to 64bit challenge values) + 1 Byte (null-terminated)
        $handshakeBytesCount = @socket_recv($socket, $handshakeBuffer, 14, MSG_OOB);

        if($handshakeBytesCount < 13) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), 'Handshake package response is too short.');
        }

        //--- Handshake Unpack
        $handshakeData = unpack('c1type/N1session/Z*token', $handshakeBuffer);

        //--- Full-Stat -> Send
        $fullStatPacket = pack('c3N2c4', self::$_MAGIC_1, self::$_MAGIC_2, self::$_FULL_STAT, $sessionId, $handshakeData['token'], 0x00, 0x00, 0x00, 0x00);
        $fullstatSend   = @socket_send($socket, $fullStatPacket, strlen($fullStatPacket), MSG_EOR);

        if($fullstatSend === false) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), 'Failed to send the full-stat packet.');
        }

        //--- Full-Stat <- Receive
        $length = @socket_recv($socket, $buffer, 65536, MSG_OOB);

        // NOTE: I haven't calculated it fully but we should receive AT LEAST (!) 18 bytes,
        // because type (1 Byte), session id (4 Byte), padding (11 Byte), body (2+ Bytes).
        // @TODO Calculate the bare minimum of a healthy response
        if($length < 18) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), 'Full-stat package response is too short.');
        }

        //--- Full-Stat Unpack
        $data = unpack('c1type/N1session/c11padding/a*body', $buffer);

        //---
        socket_close($socket);

        //---
        $this->set('online', true);
        $map = explode(chr(0), $data['body']);
        $this->set('players.list', array_values(array_slice($map, 23, -2)));

        $informationMap = array_chunk(array_slice($map, 0, 16), 2);

        foreach ($informationMap as $entry) {

            list($key, $value) = $entry;

            if(
                $key === 'gametype' ||
                $key === 'game_id'  ||
                $key === 'version'  ||
                $key === 'map'
            ) {
                $this->set($key, strval($value));
            }
            //
            else if($key === 'hostname') {
                $this->set('motd', strval($value));
            }
            //
            else if($key === 'numplayers') {
                $this->set('players.online', intval($value));
            }
            //
            else if($key === 'maxplayers') {
                $this->set('players.max', intval($value));
            }
            //
            else if($key === 'plugins') {

                $plugins = [];

                if(!(empty($value))) {
                    $entries = explode(':', $value, 2);

                    if(count($entries) > 0) {
                        $this->set('software', $entries[0]);

                        if(count($entries) > 1) {
                            $plugins = array_map('trim', explode(';', $entries[1]));
                        }
                    }

                }

                $this->set('plugins', $plugins);

            }
            //
            else if(!(empty($key))) {
                throw new InternalException("Unknown information spotted.",
                    ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
                    $this,
                    [
                        'key'   => $key,
                        'value' => $value
                    ]
                );
            }

        }

        $this->setStatus(Status::OK());
        //$this->save();
        return $this->getStatus();
    }

    private function returnWithError($socket, int $status, string $message) : int
    {
        socket_close($socket);
        $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), 'Full-stat package response is too short.');
        //$this->save(Carbon::now()->addMinutes(2));
        return $this->getStatus();
    }

}