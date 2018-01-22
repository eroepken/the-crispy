<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;

class BotController extends Controller
{
    public static function send($message) {

        $client = new Guzzle();
        $client->post(env('INCOMING_WEBHOOK_URL'), [
            RequestOptions::JSON => $message
        ]);

    }
}
