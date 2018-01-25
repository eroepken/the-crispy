<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
                    'callback_id' => 'CAHGame::setNumPlayers',
                    'actions' => [
                        [
                            'name' => 'users_list',
                            'text' => 'Pick the users',
                            'type' => 'select',
                            'options' => $num_players_opts
                        ]
                    ]
                ]
            ]
        ]);


//        $event = $bot->getEvent();
//
//        $bot->replyEphemeralInThread('Oh hi. This is private.', $event['user']);
//
//        $this->thread_id = $bot->getThreadId();

//        Log::debug($event);

        // Get the users.
//        $bot->hears('(@[\w\d\-\_]+)*', function(SlackBot $bot, $users) {
//            Log::debug($users);
//            $bot->reply('Test');
//        });
    }

    public function run() {

    }

    public static function setNumPlayers($request) {

//    Log::debug($request);

        $num_users = $request['actions'][0]['selected_options'][0]['value'];

        Log::debug($num_users);

//    $actions = [];
//
//    for($i=0; $i<= $answers[0]->value; $i++) {
//        $actions[] = [
//            'name' => 'users_list',
//            'text' => 'Pick player #' . $i,
//            'type' => 'select',
//            'data_source' => 'users'
//        ];
//    }
//
//    self::$bot->replyToInteractive('Choose players', [
//        "attachments" => [
//            [
//                'text' => 'Choose users to play',
//                'attachment_type' => 'default',
//                'callback_id' => 'player_selection',
//                'actions' => $actions,
//            ]
//        ]
//    ]);
    }
}