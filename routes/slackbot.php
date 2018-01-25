<?php

use App\Bots\SlackBot;
use App\CAHGame;
use Illuminate\Support\Facades\Route;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->replyInThread('You rang?');
});

$slackbot->hearsMention('(CAH|cards against humanity)', function(SlackBot $bot) {
    $CAH = new CAHGame($bot);
    $CAH->run();
});