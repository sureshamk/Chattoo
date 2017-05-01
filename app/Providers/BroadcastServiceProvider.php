<?php

namespace App\Providers;

use App\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Pusher;
class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(BroadcastManager $broadcastManager)
    {

        $broadcastManager->extend('pusher-custom', function (Application $app, array $config) {
            return new PusherBroadcaster(
                new Pusher($config['key'], $config['secret'],
                    $config['app_id'], Arr::get($config, 'options', []))
            );
        });
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
