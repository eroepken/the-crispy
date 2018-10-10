<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ChangeKarmaJob;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(hi|hello|good morning|morning everyone|guten tag|guten morgen|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

// Listening for user karma.
$slackbot->hears('\<\@(U\w+?)\>\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    $replies = [];

    if (!empty($matches[1])) {
      foreach ($matches[1] as $i => $rec) {
        if ($rec === $event_data['user']) {
          $bot->reply('You can\'t change your own karma!');
          continue;
        }

        $action = $matches[2][$i];

        if (env('DEBUG_MODE')) {
          Log::debug('Adding user to the dispatcher.');
        }

        $return = dispatch(new ChangeKarmaJob('user', $event_data['client_msg_id'], $rec, $action));

        if (env('DEBUG_MODE')) {
          Log::debug(print_r($return, TRUE));
        }
      }
    }

    /*$replies = implode("\n", $replies);
    $bot->reply($replies);*/

});

// Listening for thing karma.
$slackbot->hears('\@([\w:-]+?)\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    $replies = [];

    if (!empty($matches[1])) {
      foreach ($matches[1] as $i => $rec) {
        $action = $matches[2][$i];

        if (env('DEBUG_MODE')) {
          Log::debug('Adding thing to the dispatcher.');
        }

        $return = dispatch(new ChangeKarmaJob('thing', $event_data['client_msg_id'], $rec, $action));

        if (env('DEBUG_MODE')) {
          Log::debug(print_r($return, TRUE));
        }
      }
    }

    /*$replies = implode("\n", $replies);
    $bot->reply($replies);*/
});

$slackbot->hearsMention('leaderboard$', function(SlackBot $bot) {
  $bot->reply('Here\'s the leaderboard, for your reference: ' . URL::to('/leaderboard'));
});

$slackbot->hearsMention('top\s?(\d+)$', function(SlackBot $bot, $matches) {
  $bot->reply(UserController::getTopFormatted($matches[1][0]) . "\nYou can see the whole leaderboard here: " . URL::to('/leaderboard'));
});
