<?php

namespace App\Providers;

use App\McAPICache;
use App\McAPIQueue;
use Illuminate\Support\ServiceProvider;

class McAPIServiceProvider extends ServiceProvider
{

    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        McAPICache::boot();
        McAPIQueue::boot();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
