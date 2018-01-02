<?php

namespace App;


use App\Exceptions\ExceptionCodes;
use App\Exceptions\InternalException;
use Illuminate\Database\Eloquent\Model;

abstract class McAPIResponse extends Model
{

    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function getData() {
        return $this->data;
    }

    protected function set(String $key, $value) : bool {

        if(empty(trim($key)) || trim($key) !== $key) {
            throw new InternalException('The key cannot be empty or trim($key) !== $key.', ExceptionCodes::INTERNAL_ILLEGAL_ARGUMENT_EXCEPTION(), $this, [
                'key'   => $key,
                'value' => $value
            ]);
        }

        $path = explode('.', $key);


        $temp = &$this->data;
        foreach($path as $key) {
            $temp = &$temp[$key];
        }

        $temp = $value;

        return true;

    }



    /**
     * This method fetches data.
     *
     * @param bool $force
     * @return mixed
     */
    public abstract function fetch($force = false) : int;




}