<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use App\Bots\SlackBot;

class SlackBotProvider extends ServiceProvider
{

    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SlackBot::class, function() {
            return new SlackBot();
        });
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        // Catch all for events.
        Route::post('/challenge-verify', function() {
            $request = json_decode(request()->getContent(), true);

            // Add the challenge listener.
            if ($request['type'] == 'url_verification') {
                if ($request['token'] != config('services.slack.verification_token')) {
                    return response()->json(['text' => 'An error occurred.']);
                }

                return response()->json(['challenge' => $request['challenge']]);
            }

            // Also add the slack commands.
            require_once base_path('routes/slackbot.php');

            return false;
        });

        Route::post('/crispy-interactive', function() {
            $payload = json_decode(request('payload'), true);

            $callback = $payload['callback_id'];

            if (strpos($callback, '::') > 0) {
                $callback_arr = explode('::', $callback);
            }

            if (method_exists($callback_arr[0], $callback_arr[1])) {
                call_user_func($callback, $payload);
            } else {
                Log::error('Callback function not found.');
            }

            return false;
        });
    }
}
