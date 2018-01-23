<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Bots\SlackBot;

class SlackBotProvider extends ServiceProvider
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
        $this->app->singleton(Slackbot::class, function() {
            return new SlackBot();
        });
    }
}
