<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SlackbotController;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Jobs\ChangeKarmaJob;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten morgen|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

function userKarmaRoute(SlackBot $bot, $matches) {
  $event_data = $bot->getEvent();
  dispatch(new ChangeKarmaJob('user', $event_data, $matches, $bot));
}

function thingKarmaRoute(SlackBot $bot, $matches) {
  $event_data = $bot->getEvent();
  dispatch(new ChangeKarmaJob('user', $event_data, $matches, $bot));
}

// Listening for user karma.
$slackbot->hears('\<\@(U\w+?)\>\s*(\+\+|\-\-)', 'userKarmaRoute');

// Listening for thing karma.
$slackbot->hears('\@([\w:-]+?)\s*(\+\+|\-\-)', 'thingKarmaRoute');

$slackbot->hearsMention('leaderboard$', function(SlackBot $bot) {
  $bot->reply('Here\'s the leaderboard, for your reference: ' . URL::to('/leaderboard'));
});

$slackbot->hearsMention('top\s?(\d+)$', function(SlackBot $bot, $matches) {
  $bot->reply(UserController::getTopFormatted($matches[1][0]) . "\nYou can see the whole leaderboard here: " . URL::to('/leaderboard'));
});
