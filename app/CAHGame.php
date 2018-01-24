<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CAHGame extends Model
{
    protected $thread_id;
    protected $bot;

    /**
     * CAHGame constructor.
     * @param $bot
     */
    public function __construct($bot) {
        parent::__construct();

        $this->bot = $bot;

        $bot->replyInThread('Let\'s Play! Who is playing?');
        $event = $bot->getEvent();

        $this->thread_id = $bot->getThreadId();

        Log::debug($event);

        // Get the users.
        $bot->hears('(@[\w\d\-\_]+)*', function(SlackBot $bot, $users) {
            Log::debug($users);
            $bot->reply('Test');
        });
    }

    public function run() {

    }
}
