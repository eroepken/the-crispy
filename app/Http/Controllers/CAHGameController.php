<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\CAHGame;
use App\Bots\SlackBot;
use Symfony\Component\HttpFoundation\Response;

class CAHGameController extends Controller
{
    /**
     * Make sure there are enough players and start the game.
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function verifyAndStart(Request $request) {
        $request = $request->all();

        if ($request['command'] != '/cah' && isset($request['token']) && $request['token'] != config('services.slack.verification_token')) return response('false');

        // TODO: Move the following functionality to a function inside of CAHGame.
        // Get the list of players and hand it off to the CAH game class.
        $num_players = preg_match_all('/(<@[A-Za-z0-9_\-|]+>)+/m', $request['text'], $players);

        // TODO: Uncomment these next 2 lines when ready to test with friends. This makes sure the players are unique.
        // $players = array_unique($players[0]);
        // $num_players = count($players);

        // Make sure there are enough players, but not more than supported.
        if ($num_players >= CAHGame::MIN_REQUIRED && $num_players <= CAHGame::MAX_SUPPORTED) {
            $CAH = new CAHGame();
            $CAH->setupGame($players[0], $request['channel_id'], $request['response_url']);
            $CAH->save();
            $CAH->run();
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

            // Send the error message back as an ephemeral message.
            return response($message);
        }
    }

    /**
     * Specify the data returns for various interactive fields.
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getCardData(Request $request) {
        Log::debug('Sending cards...');

        if (isset($request['token']) && $request['token'] != config('services.slack.verification_token')) {
            Log::error('Token failed to validate. Halting.');
            return response('false');
        }

        Log::debug($request['payload']);

        switch($request['name']) {
            case 'choose_cah_cards':
                $instance = \App\CAHGame::where('thread_id', $request['action_ts'])
                    ->where('deleted_at', NULL)
                    ->take(1)->first(['players'])->toArray();
                $players = json_decode($instance['players'], true);

                $player_key = '<@' . $request['user']['id'] . '|' . $request['user']['name'] . '>';

                $options = $players[$player_key]['hand'];

                Log::debug(print_r($players[$player_key], true));

                return response()->json($options);

                break;

            default:
                break;
        }

        return response('true');
    }
}
