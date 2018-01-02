<?php

namespace App\Exceptions;


use App\Responses\McAPIResponse;

class InternalException extends \Exception
{

    private $response;
    private $additional;
    private $debugMessage;

    public function __construct(String $debugMessage, int $code, McAPIResponse $response, array $additional)
    {

        parent::__construct(
                'Please notify us on github.com/mcapi/mcapi-3.0 and create a new issue and copy this complete JSON response and include it in your report.'.
                ' DO NOT (!) forget to remove any personal or secret information.',
            $code,
            null
        );

        $this->debugMessage = $debugMessage;
        $this->response = get_class($response);
        $this->additional = $additional;
    }

    /**
     * @return String
     */
    public function getDebugMessage(): String
    {
        return $this->debugMessage;
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