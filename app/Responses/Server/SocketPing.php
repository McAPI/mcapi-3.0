<?php

namespace App\Responses;


use App\CacheTimes;
use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;
use Carbon\Carbon;

class SocketPing extends SocketResponse
{

    public function __construct(string $host, string $port)
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

        $force = true;

        if($force === false && $this->serveFromCache()) {
            return $this->getStatus();
        }

        //--- NOTE: The Status is != OK when ServerResponse#resolveHostAndPort couldn't resolve the host and port for whatever reason.
        if($this->getStatus() !== Status::OK()) {
            $this->save(Carbon::now()->addMinutes(10));
            return $this->getStatus();
        }

        //--- Try to fetch data using the latest protocol specifications
        $status = $this->fetchLatestProtocol();
        if($status !== Status::OK()) {
            //--- Try to fetch data using the older protocol specifications
            $status = $this->fetch16Legacy();

            //if($status !== Status::OK()) {
            //    $status = $this->fetch1415Legacy();
            //}
        }

        return $status;
    }

    private function fetchLatestProtocol() : int
    {

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
        $handshakePacket = pack('c3', 0x00, 0x154, strlen($this->getHost()))
                           . $this->getHost()
                           . pack('nc', $this->getPort(), 0x01);
        $handshakePacket = pack('c', strlen($handshakePacket)) . $handshakePacket;

        $handshakeSend = @socket_write($socket, $handshakePacket, strlen($handshakePacket));

        if($handshakeSend === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'Failed to send the handshake packet.');
        }

        //--- Status Packet -> Send
        $statusPacket   = pack('c2', 0x01, 0x00);
        $statusSend     = @socket_write($socket, $statusPacket, strlen($statusPacket));

        if($statusSend === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'Failed to send the status packet.');
        }

        //--- Status Packet <- Response
        $status = $this->socketReadVarInt($socket, $length, false);
        if($status !== Status::OK()) {
            return $this->getStatus();
        }

        if($length < 10) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The packet is too short.");
        }

        //--- Check the packet type
        $packetTypeReceive = @socket_recv($socket, $packetType, 1, MSG_WAITALL);
        if($packetTypeReceive === false){
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The server didn't respond with the packet type.");
        }

        if($packetType !== pack('c', 0x00)) {
            return $this->setStatus(Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "Received an unexpected type of packet from the server.");
        }

        //--- Receive and process payload
        $this->socketReadVarInt($socket, $length);
        $bodyReceive = @socket_recv($socket, $body, $length, MSG_WAITALL);

        if($bodyReceive === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The Server didn't respond with the expected payload.");
        }

        $data = json_decode($body, true);
        if($data === null || !(is_array($data))) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The server responded with invalid JSON.");
        }

        socket_close($socket);

        //--- Identify Protocol
        $protocol = -1;

        if(array_key_exists('version', $data) && array_key_exists('protocol', $data['version'])) {
            $protocol = $data['version']['protocol'];
        }

        if($protocol === -1) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The server responded with invalid JSON.");
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

    private function fetch16Legacy()
    {

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

        //--- Handshake Packet (Legacy) -> Send
        $legacyHandshakePacket = pack('c3s2c22scs', 0xFE,
                0x01, 0xFA, 0x00,
                0x0B,  0x00,
                0x4D, 0x00, 0x43, 0x00, 0x7C, 0x00, 0x50, 0x00, 0x69, 0x00, 0x6E, 0x00, 0x67, 0x00, 0x48, 0x00, 0x6F, 0x00, 0x73, 0x00, 0x74,
                (7 + strlen($this->getHost())),
                0x4A,
                strlen($this->getHost())
            )
            . $this->getHost()
            . pack('i', $this->getPort());
        $handshakeSend          = @socket_write($socket, $legacyHandshakePacket, strlen($legacyHandshakePacket));

        if($handshakeSend === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'Failed to send the legacy handshake packet.');
        }

        $headBytesCount = @socket_recv($socket, $headBuffer, 3, MSG_WAITALL);

        if($headBytesCount !== 3) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'The head of the packet is too short.');
        }

        $head = unpack('Cidentifier/nlength', $headBuffer);
        $trashByteCount = @socket_recv($socket, $buffer, 12, MSG_WAITALL);

        if($trashByteCount !== 12) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'The trash of the packet is too short.');
        }

        $bodyByteCount = @socket_recv($socket, $buffer, ($head['length'] * 4), MSG_WAITALL);
        if($bodyByteCount < $head['length']) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'The body of the packet is too short.');
        }

        //--- Utility
        $clean = function ($value) {
            return utf8_encode(str_replace("\x00", "", $value));
        };

        $body = explode("\x00\x00", $buffer);

        $this->set('online', true);
        $this->set('software', sprintf("Unknown %s", $clean($body[0])));
        $this->set('motd', $clean($body[1]));
        $this->set('players.online', intval($clean($body[2])));
        $this->set('players.max', intval($clean($body[3])));

        socket_close($socket);

        $this->setStatus(Status::OK());
        $this->save();
        return $this->getStatus();

    }

    private function fetch1415Legacy()
    {
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

        //--- Handshake Packet (Legacy) -> Send
        $legacyHandshakePacket  = pack('c', 0xFE);
        $handshakeSend          = @socket_write($socket, $legacyHandshakePacket, strlen($legacyHandshakePacket));

        if($handshakeSend === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'Failed to send the legacy handshake (1.4-1.5) packet.');
        }

        $headBytesCount = @socket_recv($socket, $headBuffer, 4096, MSG_PEEK);

        if($headBytesCount !== 3) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'The head of the packet is too short.');
        }


        $bodyByteCount = @socket_recv($socket, $buffer, 4096, MSG_WAITALL);
        dd($bodyByteCount);

        //--- Utility
        $clean = function ($value) {
            return utf8_encode(str_replace("\x00", "", $value));
        };

        $headBytesCount = @socket_recv($socket, $headBuffer, 3, MSG_WAITALL);

        if($headBytesCount !== 3) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                'The head of the packet is too short.');
        }

        dd(unpack('c2a/cb/a*', $buffer), $clean($buffer));


        $body = explode("\x00\x00", $buffer);

        $this->set('online', true);
        $this->set('software', sprintf("Unknown %s", $clean($body[0])));
        $this->set('motd', $clean($body[1]));
        $this->set('players.online', intval($clean($body[2])));
        $this->set('players.max', intval($clean($body[3])));

        socket_close($socket);

        $this->setStatus(Status::OK());
        $this->save();
        return $this->getStatus();
    }

    private function socketReadVarInt($socket, &$length, $closeSocket = true) : int
    {
        $readBytes = 0;
        $length = 0;

        do {
            $success = @socket_recv($socket, $value, 1, MSG_WAITALL);

            if($success === false) {
                return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                    "Failed to read varint because the server didn't respond.",
                    $closeSocket);
            }

            $value = ord($value);
            $length |= ($value & 127) << ($readBytes++ * 7);
            if ($readBytes > 5) {
                return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                    'Failed to read varint because the varint is longer than 5 bytes.',
                    $closeSocket);
            }
        } while (($value & 128) === 128);

        return Status::OK();
    }

}