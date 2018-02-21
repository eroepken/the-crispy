<?php

use App\Bots\SlackBot;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('good morning', function(SlackBot $bot) {
    $bot->addReaction('wave');
});