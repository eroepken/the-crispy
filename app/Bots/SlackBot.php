<?php

namespace App\Bots;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;

class SlackBot
{

    protected $webhook;
    protected $webhook_url;
    protected $token;

    protected $request;
    protected $event;

    public function __construct() {
        $this->webhook = new Guzzle();
        $this->webhook_url = config('services.slack.webhook_url');
        $this->token = config('services.slack.token');
    }

    /**
     * When the SlackBot hears text with their name in it.
     *
     * @param $text
     * @param $callbackResponse
     */
    public function hearsMention($text, $callbackResponse) {
        $this->hearRoute($text, $callbackResponse, 'app_mention');
    }

    /**
     * When the SlackBot hears certain text.
     *
     * @param $text
     * @param $callbackResponse
     */
    public function hears($text, $callbackResponse) {
        $this->hearRoute($text, $callbackResponse, 'message');
    }

    /**
     * Catcher for hear actions.
     *
     * @param $text
     * @param $callbackResponse
     * @param $method
     */
    private function hearRoute($text, $callbackResponse, $method) {

        Log::debug($text);
        Log::debug($method);

        // Catch all for events.
        Route::post('/crispy', function() use($text, $callbackResponse, $method) {

            $event = $request = json_decode(request()->getContent(), true);

            if ($request['type'] == 'event_callback') {
                $event = $request['event'];
            }

            $this->request = $request;
            $this->event = $event;

            $this->challengeListener();

            switch($method) {
                case 'message':
                case 'app_mention':

                    if (preg_match_all('/' . $text . '/i', $event['text'])) {
                        // Call the function callback.
                        $callbackResponse($this);
                    }

                    break;

                default:
                    break;
            }

            return response('false');

        });
    }

    /**
     * Send a basic channel reply.
     *
     * @param $text
     */
    public function reply($text) {
        $response = [
            'text' => $text,
            'channel' => $this->event['channel']
        ];

        $this->send($response);
    }

    /**
     * Send the reply in the same thread.
     *
     * @param $text
     */
    public function replyInThread($text) {
        if ($this->request['type'] == 'event_callback') {
            $response_ts = $this->event['event_ts'];
        } else {
            $response_ts = $this->event['thread_ts'];
        }

        $response = [
            'text' => $text,
            'ts' => $response_ts
        ];

        $this->send($response);
    }

    /**
     * Send the challenge back when it's requested.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function challengeListener() {
        if ($this->request['type'] == 'url_verification') {
            if ($this->request['token'] != $this->token) {
                return response()->json(['text' => 'An error occurred.']);
            }

            return response()->json(['challenge' => $this->event['challenge']]);
        }
    }

    /**
     * Send the message Guzzle request to Slack.
     *
     * @param $message
     */
    private function send($response) {
        dd($response);

        $this->webhook->post($this->webhook_url, [
            RequestOptions::JSON => $response
        ]);
    }
}