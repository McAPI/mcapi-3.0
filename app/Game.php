<?php

namespace App;


class Game extends McAPIResponse
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }


    /**
     * This method fetches data.
     *
     * @param bool $force
     * @return mixed
     */
    public function fetch($force = false): int
    {
        $this->set('players', ['Jan', 'Yonas']);

        return Status::OK();
    }

}
