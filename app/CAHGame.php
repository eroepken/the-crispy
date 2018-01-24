<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Bots\SlackBot;

class CAHGame extends Model
{
    protected $thread_id;
    protected $bot;

    /**
     * CAHGame constructor.
     * @param $bot
     */
    public function __construct(SlackBot $bot) {
        parent::__construct();

        $this->bot = $bot;

        $bot->replyInThread('Let\'s Play! Who is playing?');
        $event = $bot->getEvent();

        $bot->replyEphemeralInThread('Oh hi. This is private.', $event['user']);

        $this->thread_id = $bot->getThreadId();

        Log::debug($event);

        // Get the users.
//        $bot->hears('(@[\w\d\-\_]+)*', function(SlackBot $bot, $users) {
//            Log::debug($users);
//            $bot->reply('Test');
//        });
    }

    public function run() {

    }
}
