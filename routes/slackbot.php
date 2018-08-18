<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;

$slackbot = app()->make(SlackBot::class);

$slackbot->hears('\<@' . env('BOT_UID') . '\>\s*\+\+', function(SlackBot $bot) {
  $bot->addReactions(SlackBot::pickReactionsFromList(['awthanks', 'heart', 'boom2', 'kissing_heart', 'kiss', 'grin'], 2));
});

$slackbot->hears('\<@' . env('BOT_UID') . '\>\s*\-\-', function(SlackBot $bot) {
    $bot->addReactions(SlackBot::pickReactionsFromList(['disapproval', 'fu', 'mooning', 'middle_finger', 'wtf', 'disappointed', 'face_with_raised_eyebrow'], 2));
});

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('\<\@(\w+?)\>\s*(\+\+|\-\-)', function(SlackBot $bot, $matches) {
  $event_data = $bot->getEvent();
  foreach($matches[1] as $i => $rec) {
    $user = User::firstOrNew(['slack_id' => $rec]);
    $action = $matches[2][$i];

    switch($action) {
      case '++':
        $user->addKarma();
        Log::debug('Adding karma for' . $user->slack_id);
        break;

      case '--':
        $user->subtractKarma();
        Log::debug('Subtracting karma from' . $user->slack_id);
        break;

      default:
        break;
    }
  }
});
