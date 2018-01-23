<?php
namespace App\Responses;

use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Responses\User\IdentifierTypes;
use App\Status;
use phpseclib\Crypt\RSA;

class NuVotifier extends SocketResponse
{

    private static $_SERVICE_NAME = 'mcapi.de';

    private $identifierType;
    private $identifier;

    private $requestIP;

    private $publicKey;
    private $token;

    public function __construct(string $host, string $port, string $identifier, string $token, string $publicKey, string $requestIP)
    {

        $this->identifierType = IdentifierTypes::fromIdentifier($identifier);
        $this->identifier = $identifier;

        $this->requestIP = $requestIP;

        $this->publicKey = $publicKey;
        $this->token = $token;

        parent::__construct($host, $port,null,
            [
                'host' => null,
                'port' => -1,
                'response' => null
            ],
            -1, false);
    }

    public function fetch(array $request = [], bool $force = false): int
    {

        if($this->identifierType !== IdentifierTypes::USERNAME()) {
            //--- TODO Add support for UUIDs and resolving them correctly. - This is a bit more complicated because if we cannot resolve
            //  the username directly but have to wait for an response, we also have to queue this vote. Maybe it isn't worth the hassle.
            return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "The identifier only supports plain usernames NO UUIDs.");
        }

        // Parse the public key
        // TODO Replace dependency.
        $cipher = new RSA();
        $cipher->loadKey($this->publicKey);
        $cipher->setPublicKey();

        $this->publicKey = $cipher->getPublicKey();

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

        //--- Receive Header (VOTIFIER <version> <challenge>
        $receiveHeader = @socket_recv($socket, $headBuffer, 256, MSG_WAITALL);

        if($receiveHeader === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "Failed to receive the header package.");
        }

        $headerParts = explode(' ', $headBuffer);

        if(count($headerParts) !== 3) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "The header has an invalid amount of individual components.");
        }

        $challenge = substr($headerParts[2], 0, -1);

        if(empty($challenge)) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "Failed to receive challenge from header.");
        }

        //--- Build Payload, Signature and Package
        $payload = json_encode([
            'username'      => $this->identifier,
            'serviceName'   => self::$_SERVICE_NAME,
            'timestamp'     => (time() * 1000), //--- NOTE Timestamp has to be in milliseconds.
            'address'       => $this->requestIP,
            'challenge'     => $challenge
        ]);

        $hash = hash_hmac('SHA256', $payload, $this->token, true);

        if($hash === false) {
            socket_close($socket);
            throw new InternalException('The algorithm used by hash_hmac is not available on this system.',
                    ExceptionCodes::INTERNAL_SERVER_IN_ILLEGAL_STATE_EXCEPTION(),
                    $this,
                    []
            );
        }

        $payloadSignature = base64_encode($hash);

        if($payloadSignature === false) {
            socket_close($socket);
            throw new InternalException("Failed to base64_encode the payload hash.",
                ExceptionCodes::INTERNAL_SERVER_IN_ILLEGAL_STATE_EXCEPTION(),
                $this,
                []
            );
        }

        $message = json_encode([
            'signature' => $payloadSignature,
            'payload'   => $payload
        ]);

        $package = pack('n2', 0x733A, strlen($message)) . $message;

        //--- Send Payload
        $payloadSend = @socket_send($socket, $package, strlen($package), MSG_EOF);

        if($payloadSend === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "Failed to send payload to server.");
        }

        //
        $responseReceive = @socket_recv($socket, $responseBuffer, 512, MSG_WAITALL);

        if($responseReceive === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERAL_SERVICE_UNAVAILABLE(),
                "Failed to receive an response from the server.");
        }

        socket_close($socket);
        $response = json_decode($responseBuffer, true);

        if(isset($response['status'])) {
            $this->set('response', $response['status']);
        }
        else if (isset($response['error'])) {
            $this->set('response', $response['error']);
        }

        return $this->setStatus(Status::OK());
    }

}