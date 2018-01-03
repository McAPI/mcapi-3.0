<?php

namespace App\Responses;


use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use App\Status;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

abstract class McAPIResponse extends Resource
{

    private $cacheKey;
    private $cacheStatusKey;
    private $cacheTimeInMinutes;
    private $cacheStatus;

    private $status;
    private $statusMessage;

    private $data;

    public function __construct(String $cacheKey, array $defaultData, int $cacheTimeInMinutes, bool $cacheStatus = false)
    {
        $this->cacheKey = $cacheKey;
        $this->data = $defaultData;
        $this->cacheTimeInMinutes = $cacheTimeInMinutes;
        $this->cacheStatus = $cacheStatus;
        $this->cacheStatusKey = sprintf("%s:status", $cacheKey);
    }


    public function getCacheKey() : string
    {
        return $this->cacheKey;
    }

    public function getCacheTimeInMinutes(): int
    {
        return $this->cacheTimeInMinutes;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getStatus() : int
    {
        return $this->status;
    }

    public function isCached() : bool
    {
        return Cache::has($this->cacheKey);
    }

    public function setStatus(int $status, string $statusMessage = null) : int
    {
        $this->status = $status;
        $this->statusMessage = ($statusMessage === null ? Status::toString($this->status) : $statusMessage);
        return $this->status;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function get(string $key)
    {

        $path = explode('.', $key);
        $current = $this->data;

        foreach ($path as $entry) {

            if(!(isset($entry, $current))) {
                throw new InternalException("Unknown path to get data.",
                    ExceptionCodes::INTERNAL_ILLEGAL_ARGUMENT_EXCEPTION(),
                    $this,
                    [
                        'key'   => $key,
                        'data'  => $this->data
                    ]
                );
            }

            $current = $current[$entry];
        }

        return $current;

    }

    /**
     * @param String $key
     * @param $value
     * @return bool
     * @throws InternalException
     */
    protected function set(String $key, $value) : bool
    {

        if(empty(trim($key)) || trim($key) !== $key) {
            throw new InternalException('The key cannot be empty or trim($key) !== $key.', ExceptionCodes::INTERNAL_ILLEGAL_ARGUMENT_EXCEPTION(), $this, [
                'key'   => $key,
                'value' => $value
            ]);
        }

        $path = explode('.', $key);


        $data = &$this->data;
        foreach($path as $key) {
            $data = &$data[$key];
        }

        $data = $value;

        return true;

    }

    /**
     * Sets the data to the stored in-cache value if one exists
     *
     * @return bool true, if the cache contained a value otherwise, false.
     */
    protected function serveFromCache() : bool
    {

        if($this->isCached()) {
            $this->data = Cache::get($this->getCacheKey());

            if($this->cacheStatus === true) {

                $data = explode(':', Cache::get($this->cacheStatusKey), 2);
                $this->setStatus(intval($data[0]), $data[1]);

            }

            return true;
        }

        return false;

    }

    /**
     * Saves the data in the cache.
     *
     * @param Carbon|null $time The expiry time.
     * @return Carbon The expiry time.
     * @throws InternalException thrown when the provided point in time is in the past.
     */
    protected function save(Carbon $time = null) : Carbon
    {

        if($time === null) {
            $time = Carbon::now()->addMinutes($this->cacheTimeInMinutes);
        }

        if($time->isPast()) {
            throw new InternalException("The provided timestamp is in the past.",
                ExceptionCodes::INTERNAL_ILLEGAL_ARGUMENT_EXCEPTION(),
                $this,
                [
                    'time' => $time
                ]
            );
        }

        Cache::put($this->getCacheKey(), $this->data, $time);

        if($this->cacheStatus === true) {
            Cache::put($this->cacheStatusKey, sprintf("%d:%s", $this->status, $this->statusMessage), $time);
        }

        return $time;
    }

    public abstract function fetch(array $request = [], bool $force = false) : int;


    /**
     * Gives the time when the cache expires.
     *
     * @return Carbon
     */
    public function getCacheExpire() : Carbon
    {
        try {
            return Carbon::now()->addSeconds(Redis::ttl($this->getCacheKey()));
        } catch(\Exception $e) {
            return Carbon::now()->addMinutes($this->cacheTimeInMinutes);
        }
    }

    /**
     * Gives the time when the cache was updated.
     *
     * @return Carbon
     */
    public function getCacheUpdated() : Carbon
    {
        return $this->getCacheExpire()->subMinutes($this->cacheTimeInMinutes);
    }

    /**
     * @OVERRIDE
     *
     * This method transforms all the data we want in our API response to an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'meta'  => [
                'status'    => $this->status,
                'message'   => $this->statusMessage,
            ],
            'data'          => $this->getData(),
            'cache' => [
                'stored'    => $this->isCached(),
                'updated'   => $this->getCacheUpdated(),
                'expires'   => $this->getCacheExpire()
            ]
        ];
    }

    /**
     * @OVERRIDE
     *
     * This method transforms the data into a JSON response with the correct status code.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return response()->json($this->toArray($request), $this->status);
    }

    /**
     * Gives the Guzzle instance.
     *
     * @return Client
     */
    public static function guzzle() : Client
    {
        return app(Client::class);
    }


}