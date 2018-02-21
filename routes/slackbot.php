<?php

use App\Bots\SlackBot;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('(good morning|morning everyone|guten tag|bom dia|buenos dias)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});