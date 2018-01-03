<?php

namespace App\Responses\User;


use App\Responses\McAPIResponse;
use App\Status;


class UserReputation extends McAPIResponse
{

    //---
    private static $_ENDPOINT_MC_BOUNCER_COM    = 'http://mcbouncer.com/api/getBans/%s/%s';
    private static $_ENDPOINT_GLIZER_DE         = 'http://api.glizer.de/rpc.php?api=user&uuid=%s';

    //---
    private $identifierType = null;
    private $identifier = null;

    private $information = null;

    public function __construct(string $identifier)
    {
        parent::__construct(sprintf("user.reputation.%s", $identifier), [
            'services'      => [
                'glizer' => [
                    'servers'   => []
                ],
                'mcbouncer' => [
                    'servers'   => []
                ]
            ]
        ],
            (24 * 60));

        $this->identifierType = IdentifierTypes::fromIdentifier($identifier);
        $this->identifier = $identifier;

        //--- Note: The PROFILE_ENDPOINT doesn't allow '-' in the UUID.
        //TODO Code Duplication :UUIDCheck
        if($this->identifierType === IdentifierTypes::UUIDv4()) {
            $this->identifier = str_replace('-', '', $this->identifier);
        }
    }

    public function fetch(array $request = [], bool $force = false) : int
    {

        //---
        //:UUIDCheck
        if($this->identifierType === IdentifierTypes::INVALID()) {
            return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid identifier.");
        }

        //---
        if($this->serveFromCache()) {
            return $this->setStatus(Status::OK());
        }

        //---
        $this->information = new UserInformation($this->identifier);
        $status = $this->information->fetch();

        if($status === Status::ACCEPTED()) {
            return $this->setStatus(Status::ACCEPTED(), 'User is queued.');
        } else if($status !== Status::OK()) {
            return $this->setStatus(Status::OK(), "Unknown user.");
        }

        //---
        if(
            $this->getMcBouncerCom() ||
            $this->getGlizerDe()
        ) {
            $this->save();
            return $this->setStatus(Status::OK());
        }

        return $this->setStatus(Status::OK(), "Failed to fetch data from any of the supported ban services.");

    }

    private function getMcBouncerCom() : bool
    {

        $guzzle = self::guzzle();
        $response = $guzzle->get(sprintf(self::$_ENDPOINT_MC_BOUNCER_COM, \config('keys.user.reputation.mc_bouncer_com'), $this->information->get('username')));

        if($response->getStatusCode() === Status::OK()) {

            $data = json_decode($response->getBody(), true);

            $list = [];
            foreach ($data['data'] as $entry) {
                $list[] = [
                    'name'  => $entry['server'],
                    'reason'=> $entry['reason']
                ];
            }

            $this->set('services.mcbouncer.servers', $list);

            return true;
        }

        $this->setStatus(Status::OK(), "Failed to fetch data from at least one of the supported services.");
        return false;

    }


    private function getGlizerDe() : bool
    {

        $guzzle = self::guzzle();
        $response = $guzzle->get(sprintf(self::$_ENDPOINT_GLIZER_DE, $this->information->get('uuid')));

        if($response->getStatusCode() === Status::OK()) {

            $data = json_decode($response->getBody(), true);

            if(!(isset($data['error']))) {

                dd($data);
                $list = [];
                foreach ($data as $entry) {

                    if($entry === '_size') continue;

                    $list[] = [
                        'name'  => $entry['servername'],
                        'reason'=> $entry['message']
                    ];
                }

                $this->set('services.glizer.servers', $list);

                return true;

            }

        }

        $this->setStatus(Status::OK(), "Failed to fetch data from at least one of the supported services.");
        return false;

    }


}