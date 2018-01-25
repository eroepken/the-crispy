<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Bots\SlackBot;

class CAHGame extends Model
{
    protected $thread_id;
    protected $bot;
    protected $players;

    /**
     * CAHGame constructor.
     * @param $bot
     */
    public function __construct(SlackBot $bot) {
        parent::__construct();

        $this->bot = $bot;

        $num_players_opts = [];
        for($i=4; $i<=10; $i++) {
            $num_players_opts[] = [
                'text' => "$i",
                'value' => $i
            ];
        }

        $event = $bot->getEvent();

        $bot->replyInThread('Asking <@' . $event['user'] . '> for number of players.');

        $bot->replyEphemeral('How many players?', $event['user'], [
            'attachments' => [
                [
                    'text' => 'Choose number of players to join.',
                    'attachment_type' => 'default',
                    'callback_id' => '\App\CAHGame::getNumPlayers',
                    'actions' => [
                        [
                            'name' => 'users_list',
                            'text' => 'Choose number of players',
                            'type' => 'select',
                            'options' => $num_players_opts
                        ]
                    ]
                ]
            ]
        ]);
    }

    public static function boot() {
        Route::post('/cah-game', function() {

        });
    }

    public function run() {

    }

    /**
     * Get the number of players so
     * @param $request
     */
    public static function getNumPlayers($request) {
        $slackbot = app()->make(SlackBot::class);

        $num_users = $request['actions'][0]['selected_options'][0]['value'];

        $actions = [];

        for($i=0; $i<= $num_users; $i++) {
            $actions[] = [
                'name' => 'users_list',
                'text' => 'Pick player #' . $i,
                'type' => 'select',
                'data_source' => 'users'
            ];
        }

        Log::debug($request);

//        $slackbot->replyToInteractive('Choose players', $request['response_url'], [
//            'attachments' => [
//                [
//                    'text' => 'Choose users to play',
//                    'attachment_type' => 'default',
//                    'callback_id' => '\App\CAHGame::setPlayers',
//                    'actions' => $actions,
//                ]
//            ]
//        ]);
    }

    public static function setPlayers($request) {
        $slackbot = app()->make(SlackBot::class);

        Log::debug($request);
    }
}