<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Authenticate the request for channel access.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {

        if (!Auth::check()) {
            $user = User::create([
                'name' => $request->input('username'),
                'email' => uniqid() . '@annonymous.com',
                'password' => bcrypt(rand(100, 99999)),
            ]);
            $token = $this->guard()->login($user);
            $user->token = $token;
            $broadcastData = Broadcast::auth($request);
        } else {
            $broadcastData = Broadcast::auth($request);
        }
        return $broadcastData;

    }
}
