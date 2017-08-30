<?php

namespace Rdanusha\LaravelElasticEmail;

use Illuminate\Mail\MailServiceProvider as LaravelMailServiceProvider;

class LaravelElasticEmailServiceProvider extends LaravelMailServiceProvider
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
