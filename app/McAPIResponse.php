<?php

namespace App;


use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Cache;

abstract class McAPIResponse extends Resource
{

    private $data;
    private $status;
    private $cacheKey;

    public function __construct(String $cacheKey)
    {
        $this->data = [];
        $this->cacheKey = $cacheKey;
    }

    public function getCacheKey() : String {
        return $this->cacheKey;
    }

    public function getData() {
        return $this->data;
    }

    public function getStatus() : int {
        return $this->status;
    }

    public function isCached() : bool {
        return Cache::has($this->cacheKey);
    }

    public function setStatus(int $status) : int {
        $this->status = $status;
        return $this->status;
    }

    /**
     * @param String $key
     * @param $value
     * @return bool
     * @throws InternalException
     */
    protected function set(String $key, $value) : bool {

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

    protected function serveFromCache() : bool {

        if($this->isCached()) {
            $this->data = Cache::get($this->getCacheKey());
            return true;
        }

        return false;

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
            'status'    => $this->status,
            'message'   => Status::toString($this->status),
            'data'      => $this->getData(),
            'cache'    => [
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
     * This method fetches data.
     *
     * @param Request $request
     * @param bool $force
     * @return int
     */
    public abstract function fetch(Request $request, bool $force = false) : int;

    public abstract function getCacheExpire() : Carbon;

    public abstract function getCacheUpdated() : Carbon;

    public static function guzzle() : Client {
        return app(Client::class);
    }

}