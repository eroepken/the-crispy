<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use App\Bots\SlackBot;
use App\CAHGame;

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

            $callback = $payload['callback_id'];

            Log::debug($callback);

//            if (strpos($callback, '::') > 0) {
//                $callback_arr = explode('::', $callback);
//            }

//            if (method_exists($callback_arr[0], $callback_arr[1])) {
                call_user_func($callback, $payload);
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
