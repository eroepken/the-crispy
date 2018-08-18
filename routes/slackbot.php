<?php

use App\Bots\SlackBot;
use App\Http\Controllers\UserController;

$slackbot = app()->make(SlackBot::class);

$slackbot->hearsMention('\+\+', function(SlackBot $bot) {
    $bot->addReaction('awthanks');
    $bot->addReaction('heart');
});

$slackbot->hearsMention('\-\-', function(SlackBot $bot) {
    $bot->addReaction('disapproval');
    $bot->addReaction('fu');
});

$slackbot->hearsMention('(hello|hi)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('^(good morning|morning everyone|guten tag|bom dia|buenos dias|good day|good evening|good night|goodnight)', function(SlackBot $bot) {
    $bot->addReaction('wave');
});

$slackbot->hears('\<\@(\w+?)\>\s*(\+\+|\-\-)', function(SlackBot $bot) {
  $event_data = $bot->getEvent();
  $recipient_ids = SlackBot::extractUserIds($event_data['text']);
  Log::debug($recipient_ids);
//  foreach($recipient_ids as $rec) {
//    $user = User::firstOrNew(['slack_id' => $rec]);
//    switch($action) {
//      case '++':
//        $user->addKarma();
//        break;
//
//      case '--':
//        $user->subtractKarma();
//        break;
//
//      default:
//        break;
//    }
//  }
});
