<?php

use App\Bots\SlackBot;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('\+\+', function(SlackBot $bot) {
    $bot->addReaction('awthanks');
});

$slackbot->hearsMention('now at \d+ points', function(SlackBot $bot) {
    $bot->addReaction('fuckyeah');
});

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});