<?php

namespace Fundamental\Transcard;

use Illuminate\Support\ServiceProvider;

class TranscardServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/transcard.php' => config_path('transcard.php'),
        ], 'config');
    }
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('transcard', function ($app) {
            return new Transcard($app);
        });

        config([
            'config/transcard.php',
        ]);
    }
}