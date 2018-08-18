<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;
use App\User;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('\<\@(\w+?)\>\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
  $event_data = $bot->getEvent();

  if (count($matches[1]) > 1) {
    $all_slack_users = $bot->getUserList();
  }

  foreach($matches[1] as $i => $rec) {
    $user = User::firstOrNew(['slack_id' => $rec]);

    if (!$user->exists) {
      $user->slack_id = $rec;
      $user->karma = 0;
    }

    $user_info = $bot->getUserInfo($rec);
    Log::debug(json_encode($user_info));

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
  }
});
