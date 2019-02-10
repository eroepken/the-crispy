<?php

namespace App\Bots;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Response;

class SlackBot
{

    protected $http_client;
    protected $webhook_client;
    protected $verification_token;
    protected $bot_token;
    protected $was_mentioned = false;

    // Slack only allows for 23 max reactions to a post.
    const MAX_REACTIONS = 23;
    const FU_REACTIONS = ['disapproval', 'fu', 'mooning', 'middle_finger', 'wtf', 'disappointed', 'face_with_raised_eyebrow', 'broken_heart', 'white_frowning_face', 'angry'];
    const YAY_REACTIONS = ['awthanks', 'heart', 'boom2', 'kissing_heart', 'kiss', 'grin', 'raised_hands', 'i_love_you_hand_sign', 'heartbeat', 'revolving_hearts'];

    // Ignore code and quotes for incoming messages.
    const IGNORE_CODE_AND_QUOTES = true;

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
     * Catcher for hear actions.
     * @param $text
     * @param $callbackResponse
     * @param $method
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    private function hearRoute($text, $callbackResponse, $method) {

        $event = $this->getEvent();

        Log::debug(print_r($event, true));

        if (self::IGNORE_CODE_AND_QUOTES) {
            // If it's a quote, just cancel the whole action.
            if (preg_match('/^&gt;/', $event['text'])) {
                return response('false');
            }

            // Filter out code blocks.
            $event['text'] = preg_replace('/`{1,3}\n*.*\n*`{1,3}/i', '', $event['text']);
        }

        if (isset($event['subtype']) && in_array($event['subtype'], ['bot_message', 'message_deleted'])) {
            return response('false');
        }

        // Make sure we're not listening for the bot's own messages too.
        if (isset($event['text'])
            && ($method == 'message' || (preg_match_all('/\<@' . env('BOT_UID') . '\>/i', $event['text']) && $method == 'app_mention'))
            && preg_match_all('/' . $text . '/i', $event['text'], $matches)) {

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
     * @param $channel
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reply($text, $channel = '', $options = []) {
        $method = 'chat.postMessage';

        if (empty($channel)) {
            $channel = $this->getChannelId();
        }

        $params = array_merge([
            'token' => $this->bot_token,
            'channel' => $channel,
            'text' => $text
        ], $options);

        return $this->send($params, $method);
    }

    /**
     * Send an ephemeral reply.
     * @param $text
     * @param $user
     * @param $channel
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function replyEphemeral($text, $user, $channel = '', $options = []) {
        if (empty($channel)) {
            $channel = $this->getChannelId();
        }

        $method = 'chat.postEphemeral';

        $params = array_merge([
            'token' => $this->bot_token,
            'channel' => $channel,
            'text' => $text,
            'user' => $user
        ], $options);

        return $this->send($params, $method);
    }

    /**
     * Send the reply in the same thread.
     * @param $text
     * @param $thread_id
     * @param $channel
     * @param $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function replyInThread($text, $thread_id = '', $channel = '', $options = []) {
        if (empty($thread_id)) {
            $thread_id = $this->getThreadId();
        }
        if (empty($channel)) {
            $channel = $this->getChannelId();
        }

        $options = array_merge([
            'thread_ts' => $thread_id
        ], $options);
        return $this->reply($text, $channel, $options);
    }

    /**
     * Reply to a response_url.
     * @param $text
     * @param $channel
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function respondToURL($text, $channel = '', $options = []) {
        if (isset($options['attachments'])) {
            $options['attachments'] = json_encode($options['attachments']);
        }

        if (empty($channel)) {
            $channel = $this->getChannelId();
        }

        $method = 'chat.postMessage';

        $params = array_merge([
            'token' => $this->bot_token,
            'channel' => $channel,
            'text' => $text
        ], $options);

        return $this->send($params, $method);
    }

    /**
     * Send an interactive message.
     * @param $message
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function replyInteractive($message) {
        $method = 'chat.postMessage';

        if (empty($channel)) {
            $channel = $this->getChannelId();
        }

        $params = array_merge([
            'token' => $this->bot_token,
            'channel' => $channel
        ], $message);

        return $this->send($params, $method);
    }

    /**
     * Send an interactive message.
     * @param $message
     * @param $user
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function replyInteractiveEphemeral($message, $user) {
        $method = 'chat.postEphemeral';

        if (empty($channel)) {
            $channel = $this->getChannelId();
        }

        $params = array_merge([
            'token' => $this->bot_token,
            'channel' => $channel,
            'user' => $user
        ], $message);

        return $this->send($params, $method);
    }

    /**
     * Send a reaction emoji to the previous message.
     * @param $reaction Reaction name string.
     */
    public function addReaction($reaction, $options = []) {
        $method = 'reactions.add';

        $params = array_merge([
            'token' => $this->bot_token,
            'channel' => $this->getChannelId(),
            'name' => $reaction,
            'timestamp' => $this->getThreadId()
        ], $options);

        return $this->send($params, $method);
    }

    /**
     * @param $reactions  An array of reactions to add to a post.
     * @param array $options
     */
    public function addReactions($reactions, $options = []) {
      if (is_array($reactions) && count($reactions) > self::MAX_REACTIONS) {
        $reactions = array_slice(0, self::MAX_REACTIONS);
      }

      foreach($reactions as $reaction) {
        $this->addReaction($reaction, $options);
      }
    }

    /**
     * Get the whole detailed user list for the Slack team.
     * @param int $limit
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getUserList($limit = 0) {
        $params = array_merge([
          'token' => $this->bot_token,
          'pretty' => true,
        ]);

        return $this->get($params, 'users.list');
    }

    /**
     * Get a single user's information.
     * @param $user_id
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getUserInfo($user_id) {
        $params = array_merge([
          'token' => $this->bot_token,
          'user' => $user_id,
          'pretty' => true,
        ]);

        $response = $this->get($params, 'users.info');

        return $response['user'];
    }

    /**
     * Send the message Guzzle request to Slack.
     * @param $params
     * @param $method
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function send($params, $method) {
        if (isset($params['attachments'])) {
          $params['attachments'] = json_encode($params['attachments']);
        }

        return $this->http_client->post($method, [
            RequestOptions::FORM_PARAMS => $params
        ]);
    }

    /**
     * Send the message Guzzle request to Slack.
     * @param $params
     * @param $method
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function get($params, $method) {
        return json_decode($this->http_client->get($method, [
          RequestOptions::QUERY => $params
        ])->getBody()->getContents(), true);
    }

    /**
     * Get the whole request object to which the bot must respond.
     * @return mixed
     */
    private function getRequest() {
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
    private function getThreadId() {
        $event = $this->getEvent();
        return (empty($event['thread_ts'])) ? $event['ts']: $event['thread_ts'];
    }

    /**
     * Get the channel ID where the bot must act.
     * @return mixed
     */
    private function getChannelId() {
        $event = $this->getEvent();

        if (isset($event['channel'])) {
            return $event['channel'];
        }

        return $event['channel_id'];
    }

    /**
     * Extract the user ID from the user link string given from Slack.
     * @param $user_string
     */
    public static function extractUserId($user_string) {
        preg_match('/<@(U[0-9A-Za-z]+)\|/', $user_string, $matches);
        return $matches[0];
    }

    /**
     * Choose and return random reactions from a list of reactions.
     *
     * @param $list
     * @param $num
     * @return mixed
     */
    public static function pickReactionsFromList($list, $num) {
      return array_rand(array_flip($list), $num);
    }
}
