<?php

namespace App\Responses;


use App\CacheTimes;
use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;
use Carbon\Carbon;

class ServerPing extends ServerResponse
{

    public function __construct(string $host, string $port, string $version)
    {
        parent::__construct($host, $port, sprintf('server.ping.%s.%d', $host, $port), [
                'online'    => false,
                'host'      => null,
                'port'      => -1,
                'software'  => null,
                'motd'      => null,
                'players'   => [
                    'online'    => -1,
                    'max'       => -1,
                    'list'      => []
                ],
                'favicon'   => null,
            ],
            CacheTimes::SERVER_PING(),
        true
        );
    }


    public function fetch(array $request = [], bool $force = false): int
    {

        if($this->serveFromCache()) {
            return $this->getStatus();
        }

        //--- NOTE: The Status is != OK when ServerResponse#resolveHostAndPort couldn't resolve the host and port for whatever reason.
        if($this->getStatus() !== Status::OK()) {
            $this->save(Carbon::now()->addMinutes(10));
            return $this->getStatus();
        }

        //--- Create
        $socket = null;
        $created = $this->createSocket($socket, SOCK_STREAM, SOL_TCP);
        if($created !== Status::OK()) {
            return $this->getStatus();
        }

        //--- Connect
        $connected = $this->connectSocket($socket);
        if($connected !== Status::OK()) {
            return $this->getStatus();
        }

        //--- Handshake Packet -> Send
        $handshakePacket    = pack('c3', 0x00, 0x04, strlen($this->getHost()))
                              . $this->getHost()
                              . pack('nc', $this->getPort(), 0x01);
        $handshakePacket    = pack('c', strlen($handshakePacket)) . $handshakePacket;

        $handshakeSend      = @socket_write($socket, $handshakePacket, strlen($handshakePacket));
        if($handshakeSend === false) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), 'Failed to send the handshake packet.');
        }

        //--- Status Packet -> Send
        $statusPacket   = pack('c2', 0x01, 0x00);
        $statusSend     = @socket_write($socket, $statusPacket, strlen($statusPacket));

        if($statusSend === false) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), 'Failed to send the status packet.');
        }

        $status = $this->socketReadVarInt($socket, $length);
        if($status !== Status::OK()) {
            return $this->getStatus();
        }

        if($length < 10) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(), "The packet is too short.");
        }

        //--- Check the packet type
        $packetTypeReceive = @socket_recv($socket, $packetType, 1, MSG_WAITALL);
        if($packetTypeReceive === false){
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(),
                "The server didn't respond with the packet type.");
        }

        if($packetType !== pack('c', 0x00)) {
            return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Received an unexpected type of packet from the server.");
        }

        //--- Receive and process payload
        $this->socketReadVarInt($socket, $length);
        $bodyReceive = socket_recv($socket, $body, $length, MSG_WAITALL);

        if($bodyReceive === false) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(),
                "The Server didn't respond with the expected payload.");
        }

        $data = json_decode($body, true);

        if($data === null || !(is_array($data))) {
            return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(),
                "The server responded with invalid JSON.");
        }

        socket_close($socket);

        //--- Identify Protocol
        $protocol = -1;

        if(array_key_exists('version', $data) && array_key_exists('protocol', $data['version'])) {
            $protocol = $data['version']['protocol'];
        }

        if($protocol === -1) {
            throw new InternalException("Failed to identify protocol version.",
                ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
                $this,
                [
                    'data' => $data
                ]
            );
        }


        if($protocol >= 0) {
            $this->set('online', true);
            $this->set('software', $data['version']['name']);
            $this->set('players.online', intval($data['players']['online']));
            $this->set('players.max', intval($data['players']['max']));

            if(is_array($data['description'])) {
                $this->set('motd', $data['description']['text']);
            } else {
                $this->set('motd', $data['description']);
            }

            if(isset($data['players']['sample'])) {
                $this->set('players.list', array_map(function ($entry) {
                    return $entry['name'];
                }, $data['players']['sample']));
            }

            if(isset($data['favicon'])) {
                $this->set('favicon', $data['favicon']);
            }

            $this->setStatus(Status::OK());
            $this->save();
            return $this->getStatus();
        }

        throw new InternalException("No implementation for the protocol found.",
            ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
            $this,
            [
                'protocol'  => $protocol,
                'data'      => $data
            ]
        );

    }

    private function socketReadVarInt($socket, &$length) : int
    {
        $readBytes = 0;
        $length = 0;

        do {
            $success = @socket_recv($socket, $value, 1, MSG_WAITALL);

            if($success === false) {
                return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(),
                    "Failed to read varint because the server didn't respond.");
            }

            $value = ord($value);
            $length |= ($value & 127) << ($readBytes++ * 7);
            if ($readBytes > 5) {
                return $this->returnWithError($socket, Status::ERROR_CLIENT_BAD_REQUEST(),
                    'Failed to read varint because the varint is longer than 5 bytes.');
            }
        } while (($value & 128) === 128);

        return Status::OK();
    }

}