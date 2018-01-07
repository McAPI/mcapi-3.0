<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\ServiceProvider;

class GuzzleProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('GuzzleHttp\Client', function () {
            return new Client([
                'http_errors'   => false,
                'headers'       => [
                    'User-Agent'    => 'mcapi/3 (+mcapi.de)'
                ],
                'timeout'       => 2,
            ]);
        });
    }
}
