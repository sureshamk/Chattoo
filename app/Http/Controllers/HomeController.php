<?php

namespace App\Http\Controllers;

use App\Jobs\SendReminderEmail;
use App\User;
use Illuminate\Http\Request;
use Pusher;
use JWTAuth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //  $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function pusherAuth(Request $request)
    {
        $pusher = new Pusher(config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'), config('broadcasting.connections.pusher.app_id'));


        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            $user = User::create([
                'name' => $request->input('username'),
                'email' => uniqid() . '@annonymous.com',
                'password' => bcrypt(rand(100, 99999)),
            ]);
            dispatch(new SendReminderEmail($user));

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            $user = User::create([
                'name' => $request->input('username'),
                'email' => uniqid() . '@annonymous.com',
                'password' => bcrypt(rand(100, 99999)),
            ]);
            dispatch(new SendReminderEmail($user));

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            $user = User::create([
                'name' => $request->input('username'),
                'email' => uniqid() . '@annonymous.com',
                'password' => bcrypt(rand(100, 99999)),
            ]);
            dispatch(new SendReminderEmail($user));
        }


        $auth = $pusher->presence_auth(
            $request->input('channel_name'),
            $request->input('socket_id'),
            uniqid(),
            [
                'username' => $request->input('username'),
                'token' => JWTAuth::fromUser($user),
                'hash'=>md5($user->name)

            ]
        );
        return $auth;
    }
}
