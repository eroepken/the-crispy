<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Bots\SlackBot;
use App\Jobs\ChangeKarmaJob;

class SlackbotController extends Controller
{
  public static function userKarma(SlackBot $bot, $matches) {

  }

  public static function thingKarma(SlackBot $bot, $matches) {
    $event_data = $bot->getEvent();
    dispatch(new ChangeKarmaJob('thing', $event_data, $matches, $bot));
  }
}
