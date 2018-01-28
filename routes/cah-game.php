<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\CAHGame;
use App\Bots\SlackBot;

Route::post('/cah-game', function() {
    $request = request()->all();
    $bot = app()->make(SlackBot::class);

    Log::debug($request);

    if ($request['command'] != '/cah' && isset($request['token']) && $request['token'] != config('services.slack.verification_token')) return response('false');

    // TODO: Move the following functionality to a function inside of CAHGame.
    // Get the list of players and hand it off to the CAH game class.
    $num_players = preg_match_all('/(<@[A-Za-z0-9_\-|]+>)+/m', $request['text'], $players);

    // Make sure there are enough players, but not more than supported.
    if ($num_players >= CAHGame::MIN_REQUIRED && $num_players <= CAHGame::MAX_SUPPORTED) {
        $CAH = new CAHGame($players[1], $request['response_url']);
    } else {
        $message = '';

        if ($num_players < CAHGame::MIN_REQUIRED) {
            $num_to_get = CAHGame::MIN_REQUIRED - $num_players;
            $player_label = ($num_to_get == 1) ? 'player' : 'players';
            $message .= 'Sorry, you need at least ' . CAHGame::MIN_REQUIRED . ' players to play Cards Against Humanity. Grab ' . $num_to_get . ' more ' . $player_label . ' to start.';
        } elseif ($num_players > CAHGame::MAX_SUPPORTED) {
            $num_to_kick = $num_players - CAHGame::MAX_SUPPORTED;
            $player_label = ($num_to_kick == 1) ? 'player' : 'players';
            $message .= 'Sorry, this implementation of Cards Against Humanity only supports up to ' . CAHGame::MAX_SUPPORTED . ' players. You need to kick ' . $num_to_kick . ' ' . $player_label . ' out of the game.';
        }

        // Send the error message back.
//        $bot->respondToURL($message, $request['response_url']);
        return response($message);
    }

    return response('false');
});