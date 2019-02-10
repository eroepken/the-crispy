<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('^(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten morgen|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

// Listening for user karma.
$slackbot->hears('\<\@(U\w+?)\>\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    $replies = [];

    $actions = array_combine($matches[1], $matches[2]);

    foreach($actions as $rec => $action) {
        $user = User::firstOrNew(['slack_id' => $rec]);
        if (!$user->exists) {
            $user->slack_id = $rec;
            $user->karma = 0;
        }

        if ($rec === $event_data['user']) {
            $user->save();
            $bot->reply('You can\'t change your own karma! <@' . $user->slack_id . '> still at ' . $user->karma . ' points.');
            continue;
        }

        switch($action) {
            case '++':
                $user->karma++;
                if ($user->slack_id === env('BOT_UID')) {
                    $bot->addReactions(SlackBot::pickReactionsFromList(SlackBot::YAY_REACTIONS, 2));
                }
                break;

            case '--':
                $user->karma--;
                if ($user->slack_id === env('BOT_UID')) {
                    $bot->addReactions(SlackBot::pickReactionsFromList(SlackBot::FU_REACTIONS, 2));
                }
                break;

            default:
              break;
        }

        $user->save();
        $replies[$user->slack_id] = '<@' . $user->slack_id . '> now has ' . $user->karma . ' ' . (abs($user->karma) === 1 ? 'point' : 'points') . '.';
    }

    $replies = implode("\n", $replies);
    $bot->reply($replies);

});

// Listening for thing karma.
$slackbot->hears('\@([\w:-]+?)\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    $existing_things = DB::table('things')->select('name', 'karma')->whereIn('name', $matches[1])->get();
    $replies = [];

    $actions = array_combine($matches[1], $matches[2]);

    foreach($actions as $rec => $action) {

        // Create a new record if it doesn't exist.
        if (!$existing_things->contains('name', $rec)) {
            DB::table('things')->insert(['name' => $rec, 'karma' => 0]);
        }

        switch($action) {
            case '++':
                DB::table('things')->where('name', '=', $rec)->increment('karma');
                break;

            case '--':
                DB::table('things')->where('name', '=', $rec)->decrement('karma');
                break;

            default:
                break;
        }

        $updated = DB::table('things')->select('karma')->where('name', $rec)->get()->first();
        $replies[$rec] = '@' . $rec . ' now has ' . $updated->karma . ' ' . (abs($updated->karma) === 1 ? 'point' : 'points') . '.';
    }

    $replies = implode("\n", $replies);
    $bot->reply($replies);
});

$slackbot->hearsMention('leaderboard$', function(SlackBot $bot) {
    $bot->reply('Here\'s the leaderboard, for your reference: ' . URL::to('/leaderboard'));
});

$slackbot->hearsMention('top\s?(\d+)$', function(SlackBot $bot, $matches) {
    $bot->reply(UserController::getTopFormatted($matches[1][0]) . "\nYou can see the whole leaderboard here: " . URL::to('/leaderboard'));
});

$slackbot->seesReaction(['+1','thumbsup'], function(SlackBot $bot) {
    $bot->replyInThread('Testing reaction visibility.');
});
