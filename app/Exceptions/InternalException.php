<?php

namespace App\Exceptions;


use App\McAPIResponse;

class InternalException extends \Exception
{

    private $response;
    private $additional;
    private $trace;

    public function __construct(String $message, int $code, McAPIResponse $response, array $additional)
    {
        parent::__construct(
                'Please notify us on github.com/mcapi/mcapi-3.0 and create a new issue and copy this complete JSON response and include it in your report.'.
                ' DO NOT (!) forget to remove any personal or secret information.',
            $code,
            null
        );

        $this->response = get_class($response);
        $this->additional = $additional;
    }

    /**
     * @return array|void
     */
    public function getCachedTrace()
    {
        return $this->trace;
    }

    /**
     * @return string
     */
    public function getResponse() : string
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getAdditional() : array
    {
        return $this->additional;
    }

}