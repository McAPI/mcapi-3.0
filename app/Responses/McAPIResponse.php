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

abstract class McAPIResponse extends Resource
{

    private $cacheKey;

    private $status;
    private $statusMessage;

    private $data;

    public function __construct(String $cacheKey, array $defaultData)
    {
        $this->cacheKey = $cacheKey;
        $this->data = $defaultData;
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

    public function getCacheKey() : string
    {
        return $this->cacheKey;
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

    protected function serveFromCache() : bool
    {

        if($this->isCached()) {
            $this->data = Cache::get($this->getCacheKey());
            return true;
        }

        return false;

    }

    public abstract function fetch(Request $request, bool $force = false) : int;

    public function getCacheExpire(): Carbon
    {
        // TODO: Implement getCacheExpire() method.
        return Carbon::now();
    }

    public function getCacheUpdated(): Carbon
    {
        // TODO: Implement getCacheUpdated() method.
        return Carbon::now();
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

    public static function guzzle() : Client
    {
        return app(Client::class);
    }


}