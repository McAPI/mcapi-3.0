<?php

namespace App\Responses\User;

use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Jobs\UserInformationProcess;
use App\Responses\McAPIResponse;
use App\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;


class UserInformation extends McAPIResponse
{

    //--- Endpoints
    private static $_MINECRAFT_ENDPOINT     = 'https://api.mojang.com/users/profiles/minecraft/%s';
    private static $_PROFILE_ENDPOINT       = 'https://api.mojang.com/user/profiles/%s/names';
    private static $_PROPERTIES_ENDPOINT    = 'https://sessionserver.mojang.com/session/minecraft/profile/%s?unsigned=false';


    //---
    private $identifierType = null;
    private $identifier = null;
    private $permanentCacheKey;

    public function __construct(string $identifier)
    {
        parent::__construct(sprintf("user.information.%s", $identifier), [
                'premium'       => false,
                'uuid'          => null,
                'username'      => null,
                'history'       => [],
                'properties'    => [
                    'decoded'   => [],
                    'raw'       => []
                ]
            ],
            (1),
            true
        );

        $this->identifierType = IdentifierTypes::fromIdentifier($identifier);
        $this->identifier = $identifier;

        //--- Note: The PROFILE_ENDPOINT doesn't allow '-' in the UUID.
        //:UUIDCheck
        if($this->identifierType === IdentifierTypes::UUIDv4()) {
            $this->identifier = str_replace('-', '', $this->identifier);
        }

        $this->permanentCacheKey = sprintf("%s:permanent", $this->getCacheKey());

    }

    /**
     * @return string
     */
    public function getPermanentCacheKey(): string
    {
        return $this->permanentCacheKey;
    }

    /**
     * This method fetches data.
     *
     * @param array $request
     * @param bool $force
     * @return int
     * @throws InternalException
     */
    public function fetch(array $request = [], bool $force = false): int
    {

        //---
        //:UUIDCheck
        if($this->identifierType === IdentifierTypes::INVALID()) {
            return $this->setStatus(Status::ERROR_CLIENT_BAD_REQUEST(), "Invalid identifier.");
        }

        //---
        if($force === true) {

            switch ($this->identifierType) {

                case IdentifierTypes::USERNAME(): {
                    if(
                        $this->fetchMinecraftEndpoint() &&
                        $this->fetchProfileEndpoint() &&
                        $this->fetchPropertiesEndpoint()
                    ) {
                        $this->set('premium', true);
                        $this->setStatus(Status::OK());
                        $this->save();
                        $this->getStatus();
                    }
                } break;

                case IdentifierTypes::UUIDv4(): {
                    if(
                        $this->fetchProfileEndpoint() &&
                        $this->fetchMinecraftEndpoint() &&
                        $this->fetchPropertiesEndpoint()
                    ) {
                        $this->set('premium', true);
                        $this->setStatus(Status::OK());
                        $this->save();
                        return $this->getStatus();
                    }
                } break;

                default: throw new InternalException("Missing implementation for IdentifierType.",
                    ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
                    $this,
                    [
                        'identifier'        => $this->identifier,
                        'identifierType'    => $this->identifierType
                    ]
                );

            }

            //NOTICE: The Mojang API reports a 429 when we have hit the rate limit.
            if($this->getStatus() === Status::ERROR_TOO_MANY_REQUESTS()) {
                $this->setStatus(Status::ERROR_TOO_MANY_REQUESTS());
                $this->save(Carbon::now()->addMinutes(10));
            }
            //NOTICE: If the status is OK & we failed to fetch data then the account probably doesn't exist OR the Mojang API has some problems right now,
            // either way, we should store it.
            else if($this->getStatus() === Status::OK()) {
                $this->setStatus(Status::OK(), "The account either doesn't exist or Mojang's server are struggling right now.");
                $this->save();
            }

            return $this->getStatus();
        }

        //---
        if($this->serveFromCache()) {
            return $this->getStatus();
        }

        dispatch((new UserInformationProcess($request, $this)));

        if($this->isPermanentlyCached()) {
            $this->setData(Cache::get($this->getPermanentCacheKey()));
            return $this->setStatus(Status::OK());
        }

        return $this->setStatus(Status::ACCEPTED());

    }

    /**
     * @OVERRIDE
     * @return bool
     * @throws InternalException thrown when the code goes into a state it should never reach.
     */
    protected function serveFromCache() : bool
    {

        parent::serveFromCache();

        //---
        if($this->isCached() && $this->isPermanentlyCached()) {
            $this->setData(Cache::get($this->getCacheKey()));
            return true;
        }
        //---
        else if($this->isPermanentlyCached() && !($this->isCached())) {
            $this->setData(Cache::get($this->permanentCacheKey));
            return false;
        }
        //---
        else if(!($this->isPermanentlyCached()) && $this->isCached()) {
            throw new InternalException("The data is in the temp-cache but NOT in the permanent-cache. This should never happen.",
                ExceptionCodes::INTERNAL_ILLEGAL_STATE_EXCEPTION(),
                $this,
                []
            );
        }
        //
        else {
            return false;
        }
    }

    /**
     * @OVERRIDE
     * @param Carbon|null $time
     * @return Carbon
     * @throws InternalException
     */
    protected function save(Carbon $time = null) : Carbon
    {
        $time = parent::save($time);
        Cache::forever($this->getPermanentCacheKey(), $this->getData());
        return $time;
    }

    public function isPermanentlyCached() : bool {
        return Cache::has($this->permanentCacheKey);
    }

    private function fetchMinecraftEndpoint() : bool
    {
        $client = self::guzzle();

        $identifier = ($this->identifierType === IdentifierTypes::USERNAME() ? $this->identifier : $this->get('username'));
        $response = $client->get(sprintf(self::$_MINECRAFT_ENDPOINT, $identifier));

        if($response->getStatusCode() === Status::OK()) {

            $data = json_decode($response->getBody(), true);
            $this->set('uuid', IdentifierTypes::cleanUUID($data['id']));
            $this->set('username', $data['name']);

            return true;

        }

        $this->setStatus($response->getStatusCode() === Status::ERROR_TOO_MANY_REQUESTS()  ? Status::ERROR_TOO_MANY_REQUESTS() : Status::OK(),
            "We couldn't reach the MINECRAFT_ENDPOINT to fetch data."
        );
        return false;

    }

    private function fetchProfileEndpoint() : bool
    {
        $client = self::guzzle();

        $identifier = ($this->identifierType === IdentifierTypes::UUIDv4() ? $this->identifier : $this->get('uuid'));
        $response = $client->get(sprintf(self::$_PROFILE_ENDPOINT, $identifier));

        if($response->getStatusCode() === Status::OK()) {

            $data = json_decode($response->getBody(), true);

            $this->set('username', array_last($data)['name']);
            $this->set('history', $data);

            return true;

        }

        $this->setStatus($response->getStatusCode() === Status::ERROR_TOO_MANY_REQUESTS()  ? Status::ERROR_TOO_MANY_REQUESTS() : Status::OK(),
            "We couldn't reach the PROFILE_ENDPOINT to fetch data."
        );
        return false;
    }

    private function fetchPropertiesEndpoint() : bool
    {
        $client = self::guzzle();

        $identifier = ($this->identifierType === IdentifierTypes::UUIDv4() ? $this->identifier : $this->get('uuid'));
        $response = $client->get(sprintf(self::$_PROPERTIES_ENDPOINT, $identifier));

        if($response->getStatusCode() === Status::OK()) {

            $data = json_decode($response->getBody(), true);

            //--- RAW
            $this->set('properties.raw', $data['properties']);

            //--- Decode
            $decoded = array();
            foreach($data['properties'] as $property) {
                $decoded[] = array(
                    'name'  => $property['name'],
                    'value' => json_decode(base64_decode($property['value']), true),
                );
            }
            $this->set('properties.decoded', $decoded);

            return true;

        }

        $this->setStatus($response->getStatusCode() === Status::ERROR_TOO_MANY_REQUESTS()  ? Status::ERROR_TOO_MANY_REQUESTS() : Status::OK(),
            "We couldn't reach the PROPERTIES_ENDPOINT to fetch data."
        );
        return false;
    }

}
