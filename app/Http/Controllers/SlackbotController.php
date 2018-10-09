<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Bots\SlackBot;
use App\Jobs\ChangeKarmaJob;

class SlackbotController extends Controller
{
  public function userKarma(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    dispatch(new ChangeKarmaJob('user', $event_data, $matches, $bot));
  }

  public function thingKarma(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    dispatch(new ChangeKarmaJob('thing', $event_data, $matches, $bot));
  }
}
