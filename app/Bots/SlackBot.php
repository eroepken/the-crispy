<?php

namespace App\Bots;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class SlackBot
{

    protected $http_client;
//    protected $webhook_url;
    protected $verification_token;
    protected $bot_token;

    protected $request;
    protected $event;

    public function __construct() {
        $this->http_client = new Guzzle([
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'base_uri' => 'https://slack.com/api/',
            ]);
//        $this->webhook_url = config('services.slack.webhook_url');
        $this->verification_token = config('services.slack.verification_token');
        $this->bot_token = config('services.slack.bot_access_token');
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

        $event = $this->getEvent();

        if (preg_match_all('/' . $text . '/i', $event['text'])) {

            switch ($method) {
                case 'message':
                case 'app_mention':
                    // Call the function callback.
                    $callbackResponse($this);
                    break;

                default:
                    break;
            }
        }

        return response('false');
    }

    /**
     * Send a basic channel reply.
     *
     * @param $text
     */
    public function reply($text) {
        $event = $this->getEvent();
        $method = 'chat.postMessage';

        $response = [
            'token' => $this->bot_token,
            'text' => $text,
            'channel' => $event['channel']
        ];

        $this->send($response, $method);
    }

    /**
     * Send the reply in the same thread.
     *
     * @param $text
     */
    public function replyInThread($text) {
        $event = $this->getEvent();
        $method = 'chat.postMessage';

        $response = [
            'token' => $this->bot_token,
            'text' => $text,
            'thread_ts' => (empty($event['thread_ts'])) ? $event['ts']: $event['thread_ts'],
            'channel' => $event['channel']
        ];

        Log::debug($response);

        $this->send($response, $method);
    }

    /**
     * Send the message Guzzle request to Slack.
     *
     * @param $message
     */
    private function send($response, $method) {
        $this->http_client->post($method, [
            RequestOptions::FORM_PARAMS => $response
        ]);
    }

    /**
     * @return mixed
     */
    private function getRequest() {
        return json_decode(request()->getContent(), true);
    }

    /**
     * @return mixed
     */
    private function getEvent() {
        $request = $this->getRequest();

        if (request('type') == 'event_callback') {
            $event = $request['event'];
        } else {
            $event = $request;
        }

        return $event;
    }
}