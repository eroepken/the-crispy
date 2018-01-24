<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CAHGame extends Model
{
    protected $thread_id;
    protected $bot;

    public function __construct($bot) {
        parent::__construct();

        $this->bot = $bot;

        $bot->replyInThread('Let\'s Play!');
        $event = $bot->getEvent();

        $this->thread_id = $bot->getThreadId();

        Log::debug($event);
    }

    public function run() {

    }
}
