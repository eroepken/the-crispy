<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Bots\SlackBot;

class CAHGame extends Model
{
    use SoftDeletes;

    // Properties only used by this class.
    protected $bot;
    protected $channel;
    protected $response_url;
    protected $card_czar;

    // Keep track of the white and black cards to make sure we don't get duplicates in the same game.
    protected $white_cards;
    protected $black_cards;

    // Fields to be entered in the DB.
    public $thread_id;
    public $players;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['thread_id', 'players'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cah_games';

    // Minimum required number of players.
    const MIN_REQUIRED = 4;

    // Maximum number of players supported.
    const MAX_SUPPORTED = 10;

    // Maximum number of cards in each players hand.
    const CARDS_IN_HAND = 10;

    // The default number of black cards required to win the game.
    const POINTS_TO_WIN = 10;

    /*
     * The JSON list of cards from the API website. TBH, I think the owner would prefer if we
     * weren't pinging their server all the time.
     */
    const CARDS_FILE = '/database/card_files/cah_cards_official.json';

    /**
     * CAHGame constructor.
     * @param $players
     * @param $channel
     * @param $response_url
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @param $players
     * @param $channel
     * @param $response_url
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function setupGame($players, $channel, $response_url) {
        $this->bot = app()->make(SlackBot::class);
        $this->players = array_fill_keys($players, ['score' => 0, 'hand' => []]);
        $this->channel = $channel;
        $this->response_url = $response_url;
        $this->resetDeck();

        $players = join(' and ', array_filter(array_merge(array(join(', ', array_slice($players, 0, -1))), array_slice($players, -1)), 'strlen'));

        /**
         * Send a message to start the new game thread and get the thread ID back to store the game
         * in the database.
         */
        $message = 'A new Cards Against Humanity game commences for ' . $players . '. Come on in and play! (*Important note:* The text of this game is NSFW. You have been warned.)';
        $message_sent = $this->bot->respondToURL($message, $channel);

        if ($message_sent->getStatusCode() != 200) {
            Log::debug('Error sending the CAHGame start message. ' . $message_sent->getStatusCode() . ' ' . $message_sent->getReasonPhrase());
            return response('A weird error occurred. Check the logs to find out what\'s wrong.');
        }

        // Get the thread ID to start a new game.
        $message_sent_body = json_decode($message_sent->getBody(), true);
        $this->thread_id = $message_sent_body['ts'];

        $this->fill(['players' => json_encode($this->players), 'thread_id' => $this->thread_id]);
    }

    /**
     * Run through the game until someone gets enough black cards to win.
     */
    public function run() {
        // Deal out the cards and randomly pick the first card czar.
        $this->replenishHands();
        $this->card_czar = array_rand($this->players);

        // Make sure we set the pointer on the players array to the randomly chosen card czar.
        // TODO: Fix this with multiple players.
//        while (current($this->players) !== $this->card_czar) next($this->players);

        $this->sendGameMessage($this->card_czar . ' is your first card czar. Here\'s the first black card.');

        $gameover = false;
//        do {
            $gameover = $this->playRound();
//        } while(!$gameover);

        return response('Game is complete!');
    }

    private function playRound() {
        // Set the next card czar and pull a black card
        $black_card = $this->drawBlackCard();

        $prompt = $black_card->text;
        $num_cards_to_play = $black_card->pick;

        $this->sendGameMessage("`" . html_entity_decode($prompt) . "`");
        $this->askForCards($num_cards_to_play);

        // Ask players (not the card czar) to choose a card or cards from their hands

        // Card czar picks their favorite (select list)
        // Winner gets karma and a point added to their total score
        $this->savePlayerData();

        // Check the score: if someone has reached the points required to win, end the game, give the winner more karma and thank everyone for playing.
        // If no one has won yet, draw replacement white cards for everyone and start a new round.
        /*if ($winner = $this->hasWinner()) {
            return $this->gameOver($winner);
        } else {
            $this->replenishHands();
            $this->nextCardCzar();
            $this->sendGameMessage('No winners yet! ' . $this->card_czar . ' is your next card czar. Here\'s the next black card.');
        }*/

        // TODO: Remove the next line when we want to actually test real game play.
//        return true;
    }

    /**
     * Return the player if they won.
     * @return bool
     */
    private function hasWinner() {
        $this->refresh();

        foreach($this->players as $player => $data) {
            if ($data['points'] == $this->POINTS_TO_WIN) {
                return $player;
            }
        }

        return false;
    }

    /**
     * Assign the next card czar.
     */
    private function nextCardCzar() {
        next($this->players);
    }

    /**
     * Add the black and white cards to a trackable array so we can prevent duplicates in the same game.
     */
    private function resetDeck() {
        $all_cards = $this->getAllCards();
        $this->black_cards = $all_cards->blackCards;
        $this->white_cards = $all_cards->whiteCards;
    }

    /**
     *
     * @throws \Exception
     */
    private function gameOver($winner) {
        // Give the winning user karma and thank everyone for playing.
        $this->sendGameMessage($winner . '++ You win! Thanks for playing, everyone!');

        // Delete this game.
        try {
            $this->delete();
        } catch(\Exception $exception) {
            Log::debug($php_errormsg);
        }

        return true;
    }

    /**
     * Grab a random new white card and remove it from the deck.
     * @return mixed
     */
    private function drawWhiteCard() {
        $key = array_rand($this->white_cards);
        $chosen_card = $this->white_cards[$key];
        // Remove it so we don't see it again later in the game.
        unset($this->white_cards[$key]);
        return $chosen_card;
    }

    /**
     * Grab a random new black card and remove it from the deck.
     * @return mixed
     */
    private function drawBlackCard() {
        $key = array_rand($this->black_cards);
        $chosen_card = $this->black_cards[$key];
        // Remove it so we don't see it again later in the game.
        unset($this->black_cards[$key]);
        return $chosen_card;
    }

    /**
     *
     */
    private function replenishHands() {
        if (count($this->white_cards) < 1) {
            return FALSE;
        }

        foreach ($this->players as &$player) {
            $num_cards = count($player['hand']);
            if ($num_cards < self::CARDS_IN_HAND) {
                // Reset the array keys.
                $player['hand'] = array_values($player['hand']);

                // Grab new white cards.
                $cards_to_assign = self::CARDS_IN_HAND - $num_cards;

                // Prevent overflow error. Make sure we have enough cards left over to deal to the player.
                if (count($this->white_cards) < $cards_to_assign) {
                    $cards_to_assign = count($this->white_cards);
                }

                for ($i = 0; $i < $cards_to_assign; $i++) {
                    $player['hand'][] = [
                        'value' => $i,
                        'text' => $this->drawWhiteCard()
                    ];
                }
            }
        }

        // Update the DB with the player's new cards.
        $this->savePlayerData();
    }

    /**
     * Save the player's data to the database.
     */
    private function savePlayerData() {
        $this->update(['players' => json_encode($this->players)]);
    }

    /**
     * Get all of the cards from the JSON file.
     * @return mixed
     */
    private function getAllCards() {
        return json_decode(file_get_contents(dirname(__DIR__) . self::CARDS_FILE));
    }

    /**
     * Send a standard game message for the players.
     * @param $text
     */
    private function sendGameMessage($text) {
        $this->bot->replyInThread($text, $this->thread_id, $this->channel);
    }

    /**
     * Send a standard game message for the players.
     * @param $num_cards_to_play
     */
    private function askForCards($num_cards_to_play) {
        $card_label = ($num_cards_to_play == 1) ? 'card' : 'cards';

        $message = [
            'text' => "Please choose $num_cards_to_play $card_label from your hand.",
            'response_type' => 'in_channel',
            'attachments' => [
                [
                    'text' => '',
                    'color' => '#3AA3E3',
                    'attachment_type' => 'default',
                    'callback_id' => '\\App\\CAHGame::cardSelection',
                    'actions' => [
                        [
                            'name' => 'choose_cah_cards',
                            'text' => 'Pick a card, any card!',
                            'type' => 'select',
                            'data_source' => 'external'
                        ]
                    ]
                ]
            ]
        ];

        return $this->bot->replyInteractive($message);
    }

    /**
     * Public callback for the user card selection.
     */
    public static function cardSelection() {
        Log::debug('hi?');
//        Log::debug(print_r(request(), true));
    }
}