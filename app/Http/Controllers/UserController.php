<?php

namespace App\Http\Controllers;

use App\Responses\User\UserInformation;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function information(Request $request, string $identifier)
    {
        $information = new UserInformation($identifier);
        $information->fetch($request->all());
        return $information;
    }

}
