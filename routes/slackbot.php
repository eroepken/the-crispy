<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;
use App\User;
use Illuminate\Support\Facades\DB;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

// Listening for user karma.
$slackbot->hears('\<\@(U\w+?)\>\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();

    foreach($matches[1] as $i => $rec) {
        $user = User::firstOrNew(['slack_id' => $rec]);
        if (!$user->exists) {
            $user->slack_id = $rec;
            $user->karma = 0;
        }

        if ($rec === $event_data['user']) {
            $user->save();
            $bot->replyInThread('You can\'t change your own karma! <@' . $user->slack_id . '> still at ' . $user->karma . ' points.');
            continue;
        }

        $action = $matches[2][$i];

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
        $bot->replyInThread('<@' . $user->slack_id . '> now has ' . $user->karma . ' points.');
    }
});

// Listening for thing karma.
$slackbot->hears('\@(\w+?)\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {

    $event_data = $bot->getEvent();
    $existing_things = DB::table('things')->select('name')->whereIn('name', $matches[1])->get();

    foreach($matches[1] as $i => $rec) {
      $action = $matches[2][$i];

      $record_exists = $existing_things->where('name', $rec);
      Log::debug($record_exists);

//      if (!$record_exists) {
//        DB::table('things')->insert(['name' => $rec, 'karma' => 0]);
//      }

//      switch($action) {
//        case '++':
//          DB::table('things')->where('name', '=', $rec)->increment('karma');
//          break;
//
//        case '--':
//          DB::table('things')->where('name', '=', $rec)->decrement('karma');
//          break;
//
//        default:
//          break;
//      }
//
//      Log::debug($data);
//
//      $bot->replyInThread('@' . $rec . ' now has ' . $data->karma . ' points.');
    }
});
