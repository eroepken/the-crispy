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
    protected $response_url;

    // Minimum required number of players.
    const MIN_REQUIRED = 4;

    // Maximum number of players supported.
    const MAX_SUPPORTED = 10;

    // The default number of black cards required to win the game.
    const DEFAULT_POINTS_TO_WIN = 10;

    /**
     * CAHGame constructor.
     * @param $players
     * @param $channel
     * @param $response_url
     */
    public function __construct($players, $channel, $response_url) {
        parent::__construct();

        $this->bot = app()->make(SlackBot::class);
        $this->response_url = $response_url;

        $players = join(' and ', array_filter(array_merge(array(join(', ', array_slice($players, 0, -1))), array_slice($players, -1)), 'strlen'));

        /**
         * Send a message to start the new game thread and get the thread ID back to store the game
         * in the database.
         */
        $message = 'A new Cards Against Humanity game commences for ' . $players . '. Come on in and play! (*Important note:* The text of this game is NSFW. You have been warned.)';
        // TODO: After confirming that this functionality actually works, make sure all players are unique.
        $message_sent = $this->bot->respondToURL($message, $channel);

        if ($message_sent->getStatusCode() != 200) {
            Log::debug('Error sending the CAHGame start message. ' . $message_sent->getStatusCode() . ' ' . $message_sent->getReasonPhrase());
            return response('A weird error occurred. Check the logs to find out what\'s wrong.');
        }

        // Get the thread ID to start a new game.
        $message_sent_body = json_decode($message_sent->getBody(), true);
        $this->thread_id = $message_sent_body['ts'];

        // Send a dialog box to the initiating user to configure the game.
        Log::debug($this->thread_id);

//        $bot->replyInThread('Asking <@' . $event['user'] . '> for number of players.');
//
//        $bot->replyEphemeral('How many players?', $event['user'], [
//            'attachments' => [
//                [
//                    'text' => 'Choose number of players to join.',
//                    'attachment_type' => 'default',
//                    'callback_id' => '\App\CAHGame::getNumPlayers',
//                    'actions' => [
//                        [
//                            'name' => 'users_list',
//                            'text' => 'Choose number of players',
//                            'type' => 'select',
//                            'options' => $num_players_opts
//                        ]
//                    ]
//                ]
//            ]
//        ]);
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
    }

    public static function setPlayers($request) {
        $slackbot = app()->make(SlackBot::class);

        Log::debug($request);
    }
}