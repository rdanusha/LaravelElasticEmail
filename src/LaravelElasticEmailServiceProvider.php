<?php

namespace Rdanusha\LaravelElasticEmail;

use Illuminate\Support\ServiceProvider;

class LaravelElasticEmailServiceProvider extends ServiceProvider
{
    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            return new TransportManager($app);
        });
    }
}
