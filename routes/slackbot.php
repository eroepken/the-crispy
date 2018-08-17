<?php

use App\Bots\SlackBot;
use Illuminate\Support\Facades\Log;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('\+\+', function(SlackBot $bot) {
    $bot->addReaction('awthanks');
});

$slackbot->hearsMention('\-\-', function(SlackBot $bot) {
    $bot->addReaction('disapproval');
});

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('(\+\+|\-\-)', function($data) {
  Log::debug(print_r($data, true));
});
