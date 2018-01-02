<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{

    const STATUS = [
        'Ok'    => 200,
        'Error' => 500
    ];

}
