<?php

use App\Bots\SlackBot;
use Illuminate\Support\Facades\Route;

$slackbot = App::make('App\Bots\SlackBot');

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->reply('You rang?');
});

$slackbot->hearsMention('(CAH|cards against humanity)', function(SlackBot $bot) {
    $bot->reply('Let\'s play!');
});