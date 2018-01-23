<?php

namespace App\Http\Controllers;

use App\Responses\User\UserInformation;
use App\Responses\User\UserReputation;
use App\Responses\NuVotifier;
use Illuminate\Http\Request;

class VotingController extends Controller
{

    public function nuVotifier(Request $request, string $ip, string $port, string $identifier, string $token, string $publicKey)
    {
        $votifier = new NuVotifier($ip, $port, $identifier, $token, $publicKey, $request->ip());
        $votifier->fetch();
        return $votifier;
    }

}
