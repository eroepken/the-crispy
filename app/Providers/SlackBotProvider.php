<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
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
        // Catch all for events.
        Route::post('/crispy', function() {
            $request = json_decode(request()->getContent(), true);

            // Add the challenge listener.
            if ($request['type'] == 'url_verification') {
                if ($request['token'] != config('services.slack.token')) {
                    return response()->json(['text' => 'An error occurred.']);
                }

                return response()->json(['challenge' => $request['challenge']]);
            }

            // Also add the slack commands.
            $this->slackBotCommands();
        });

        Route::post('/crispy-interactive', function() {
            $payload = json_decode(request('payload'), true);

            Log::debug($payload);

            $callback = $payload['callback_id'];

            Log::debug($callback);

//            if (function_exists($callback)) {
//                $callback(request());
//            } else {
//                Log::error('Callback function not found.');
//            }
        });
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

    public function map() {
//        $this->mapSlackBotCommands();
    }

    /**
     * Include the routes for the Slack bot.
     */
    protected function slackBotCommands() {
        require base_path('routes/slackbot.php');
    }
}
