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

        $response = [
            'text' => $text,
            'channel' => $event['channel']
        ];

        $this->send($response);
    }

    /**
     * Send the reply in the same thread.
     *
     * @param $text
     */
    public function replyInThread($text) {
        $event = $this->getEvent();

        $response = [
            'text' => $text,
            'ts' => $event['event_ts']
        ];

        $this->send($response);
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
            $event = $request->event;
        } else {
            $event = $request;
        }

        return $event;
    }
}