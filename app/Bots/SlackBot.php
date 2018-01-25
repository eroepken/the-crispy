<?php

namespace App\Bots;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class SlackBot
{

    protected $http_client;
    protected $webhook_client;
//    protected $webhook_url;
    protected $verification_token;
    protected $bot_token;

    protected $request;
    protected $event;

    /**
     * SlackBot constructor.
     */
    public function __construct() {
        $this->http_client = new Guzzle([
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'base_uri' => 'https://slack.com/api/',
        ]);

        $this->webhook_client = new Guzzle([
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        ]);

//        $this->webhook_url = config('services.slack.webhook_url');
        $this->verification_token = config('services.slack.verification_token');
        $this->bot_token = config('services.slack.bot_access_token');
    }

    /**
     * When the SlackBot hears text with their name in it.
     * @param $text
     * @param $callbackResponse
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function hearsMention($text, $callbackResponse) {
        return $this->hearRoute($text, $callbackResponse, 'app_mention');
    }

    /**
     * When the SlackBot hears certain text.
     * @param $text
     * @param $callbackResponse
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function hears($text, $callbackResponse) {
        return $this->hearRoute($text, $callbackResponse, 'message');
    }

    /**
     * @param $text
     * @param $callbackResponse
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
//    public function hearsInThread($text, $callbackResponse, $thread_id) {
//        return $this->hearRoute($text, $callbackResponse, 'message', $thread_id);
//    }

    /**
     * Catcher for hear actions.
     * @param $text
     * @param $callbackResponse
     * @param $method
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    private function hearRoute($text, $callbackResponse, $method) {

        $event = $this->getEvent();

        if (preg_match_all('/' . $text . '/i', $event['text'], $matches) && in_array($method, ['messages', 'app_mention'])) {
            if (empty($matches)) {
                return $callbackResponse($this);
            } else {
                return $callbackResponse($this, $matches);
            }
        }

        return response('false');
    }

    /**
     * Send a basic channel reply.
     * @param $text
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reply($text, $options = []) {
        $event = $this->getEvent();
        $method = 'chat.postMessage';

        $response = array_merge([
            'token' => $this->bot_token,
            'channel' => $event['channel'],
            'text' => $text
        ], $options);

        $test = $this->send($response, $method);

        return $test;
    }

    /**
     * Send a basic channel reply.
     * @param $text
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function replyEphemeral($text, $user, $options = []) {
        $event = $this->getEvent();
        $method = 'chat.postEphemeral';

        $response = array_merge([
            'token' => $this->bot_token,
            'channel' => $event['channel'],
            'text' => $text,
            'user' => $user
        ], $options);

        return $this->send($response, $method);
    }

    /**
     * Send the reply in the same thread.
     * @param $text
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function replyInThread($text, $options = []) {
        $options = array_merge([
            'thread_ts' => $this->getThreadId()
        ], $options);
        return $this->reply($text, $options);
    }

    /**
     * Reply to an interactive message answer.
     *
     * @param $text
     * @param $url
     */
    public function replyToInteractive($text, $url, $options = []) {
        return $this->http_client->post($url, array_merge([
            'text' => $text
        ], $options));
    }

    /**
     * Send a basic channel reply.
     * @param $text
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
//    public function replyEphemeralInThread($text, $user, $options = []) {
//        $options = array_merge([
//            'thread_ts' => $this->getThreadId()
//        ], $options);
//        return $this->replyEphemeral($text, $user, $options);
//    }

    /**
     * Send the message Guzzle request to Slack.
     * @param $response
     * @param $method
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function send($response, $method) {
        if (isset($response['attachments'])) {
            $response['attachments'] = json_encode($response['attachments']);
        }

        return $this->http_client->post($method, [
            RequestOptions::FORM_PARAMS => $response
        ]);
    }

    /**
     * Get the whole request object to which the bot must respond.
     * @return mixed
     */
    public function getRequest() {
        return json_decode(request()->getContent(), true);
    }

    /**
     * Get the event information to which the bot must respond.
     * @return array
     */
    public function getEvent() {
        $request = $this->getRequest();

        if (request('type') == 'event_callback') {
            $event = $request['event'];
        } else {
            $event = $request;
        }

        return $event;
    }

    /**
     * Get the ID for the thread where the bot must act.
     * @return string
     */
    public function getThreadId() {
        $event = $this->getEvent();
        return (empty($event['thread_ts'])) ? $event['ts']: $event['thread_ts'];
    }
}