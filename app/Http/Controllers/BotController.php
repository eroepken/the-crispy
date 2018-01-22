<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;

class BotController extends Controller
{
    public static function send($message) {

        Log::debug('Sending response');

        $client = new Guzzle();
        $response = $client->post(env('INCOMING_WEBHOOK_URL'), [
            RequestOptions::JSON => $message
        ]);

        Log::debug($response);

    }
}
