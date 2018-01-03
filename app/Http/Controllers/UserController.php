<?php

namespace App\Http\Controllers;

use App\Responses\User\UserInformation;
use App\Responses\User\UserReputation;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function information(Request $request, string $identifier)
    {
        $information = new UserInformation($identifier);
        $information->fetch($request->all());
        return $information;
    }

    public function reputation(Request $request, string $identifier)
    {
        $reputation = new UserReputation($identifier);
        $reputation->fetch($request->all());
        return $reputation;
    }

}
