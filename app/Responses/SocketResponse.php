<?php
namespace App\Responses;


use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;
use Carbon\Carbon;

abstract class SocketResponse extends McAPIResponse
{

    private $host;
    private $port;

    private $ipType;

    public function __construct(string $host, string $port, $cacheKey = null, array $defaultData, $cacheTimeInMinutes = -1, $cacheStatus = false)
    {
        parent::__construct($cacheKey, $defaultData, $cacheTimeInMinutes, $cacheStatus);

        $this->resolveHostAndPort($host, $port);

        $this->set('host', $this->host);
        $this->set('port', $this->port);

        if($this->getStatus() === Status::OK()) {

            //@TODO Check if IP is in private/local range
            if (filter_var($this->host, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV4])) {
                $this->ipType = AF_INET;
            } else if (filter_var($this->host, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV6])) {
                $this->ipType = AF_INET6;
            } else {
                throw new InternalException("The host is neither IPv4 nor IPv6.",
                    ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
                    $this,
                    [
                        'host' => $this->host,
                        'port' => $this->port
                    ]
                );
            }
        }
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getPort() : int
    {
        return $this->port;
    }

    public function getIpType() : int
    {
        return $this->ipType;
    }

    /**
     * Creates a socket.
     *
     * @param mixed $socket The variable to bind the socket to.
     * @param int $type http://php.net/manual/en/function.socket-create.php
     * @param int $protocol http://php.net/manual/en/function.socket-create.php
     * @return int A status code. 200 if it was successful, otherwise 500.
     */
    protected function createSocket(&$socket, int $type, int $protocol)
    {
        $socket = @socket_create($this->getIpType(), $type, $protocol);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 0));

        if($socket === false) {
            return $this->returnWithError($socket, Status::ERROR_INTERNAL_SERVER_ERROR(), sprintf('Failed to create a socket. (%s)', socket_strerror(socket_last_error())));
        }

        return Status::OK();
    }

    /**
     * Connects a socket to the IP and Host stored in the object.
     *
     * @param resource $socket The socket to connect.
     * @return int A status code. 200 if it was successful, otherwise 500.
     */
    protected function connectSocket($socket)
    {
        socket_set_nonblock($socket);
        $connected = false;
        $now = microtime(true);
        $timeout = 2000;
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

        return Status::OK();
    }

    /**
     * Validates and resolves the host and port.
     *
     * @param string $host The host to validate and resolve.
     * @param string $port The port to validate.
     * @return bool true, if it was successful, otherwise false.
     */
    private function resolveHostAndPort(string &$host, string &$port) : bool
    {

        $this->setStatus(Status::OK());

        //---
        $port = intval($port);

        if($port < 0 || $port > 65535) {
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
        if(filter_var($host, FILTER_VALIDATE_IP)) {
            $this->host = $host;
            return true;
        }


        //---
        $_tmp = gethostbyname($host);
        $validHostname = !($host === $_tmp);

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
                $this->host = $_tmp;
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
                $this->host = gethostbyname($record['target']);

                if(isset($record['port'])) {
                    $this->port = intval($record['port']);
                }

                return true;

            }

            $this->host = $_tmp;
            return true;

        }

        $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid host.");
        return false;

    }

    /**
     * This method sets the status, saves the current state to the cache and can close the socket.
     *
     * @param mixed $socket The socket used to establish a connection.
     * @param int $status The status code.
     * @param string $message The status message.
     * @param bool $closeSocket If it should try to close the socket.
     * @return int The status code.
     */
    protected function returnWithError($socket, int $status, string $message, $closeSocket = true) : int
    {
        if($closeSocket) {
            @socket_close($socket);
        }

        $this->setStatus($status, $message);

        if($this->isCacheEnabled()) {
            $this->save(Carbon::now()->addMinutes(2));
        }

        return $this->getStatus();
    }

}