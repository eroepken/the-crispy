<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Bots\SlackBot;
use Mockery\Exception;

class CAHGame extends Model
{
    use SoftDeletes;

    // Properties only used by this class.
    protected $bot;
    protected $response_url;
    protected $initiating_user;

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

    // The default number of black cards required to win the game.
    const POINTS_TO_WIN = 10;

    /*
     * The JSON list of cards from the API website. TBH, I think the owner would prefer if we
     * weren't pinging their server all the time.
     */
    const CARDS_FILE = 'card_files/cah_cards_official.json';

    /**
     * CAHGame constructor.
     * @param $players
     * @param $channel
     * @param $response_url
     * @param $initiating_user
     */
    public function __construct($players, $channel, $response_url, $initiating_user) {
        parent::__construct();

        $this->bot = app()->make(SlackBot::class);
        $this->players = array_fill_keys($players, 0);
        $this->response_url = $response_url;
        $this->initiating_user = $initiating_user;

        $players = join(' and ', array_filter(array_merge(array(join(', ', array_slice($players, 0, -1))), array_slice($players, -1)), 'strlen'));

        /**
         * Send a message to start the new game thread and get the thread ID back to store the game
         * in the database.
         */
        $message = 'A new Cards Against Humanity game commences for ' . $players . '. Come on in and play! (*Important note:* The text of this game is NSFW. You have been warned.)';
        // TODO: After confirming that this functionality actually works, make sure all players are unique.
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
        $gameover = false;

        $this->bot->replyInThread($this->players[0] . ' is your first card czar. Here\'s the first black card.', $this->thread_id);

//        do {
//            $this->playRound();
//        } while(!$gameover);
    }

    private function playRound() {
        // Set the next card czar and pull a black card
        $this->drawBlackCard();
        // Ask players (not the card czar) to choose a card or cards from their hands
        // Card czar picks their favorite (select list)
        // Winner gets karma and a point added to their total score
        // Check the score: if someone has reached the points required to win, end the game, give the winner more karma and thank everyone for playing.
        // If no one has won yet, draw replacement white cards for everyone and start a new round.
        if ($winner = $this->hasWinner()) {
            $this->gameOver($winner);
        } else {
            $this->dealWhiteCards();
        }
    }

    /**
     * Return the player if they won.
     * @return bool
     */
    private function hasWinner() {
        $players = $this->getPlayers();

        foreach($players as $player => $points) {
            if ($points == $this->POINTS_TO_WIN) {
                return $player;
            }
        }

        return false;
    }

    private function getPlayers() {
        $game = self::findOrFail($this->id);
        dd($game);
    }

    /**
     *
     * @throws \Exception
     */
    private function gameOver($winner) {
        // Give the winning user karma and thank everyone for playing.
        $this->bot->replyInThread($winner . '++ You win! Thanks for playing, everyone!', $this->thread_id);

        // Delete this game.
        try {
            $this->delete();
        } catch(\Exception $exception) {
            Log::debug($php_errormsg);
        }
    }

    private function drawWhiteCard() {
        $all_cards = $this->getAllCards();
        dd($all_cards);
    }

    private function drawBlackCard() {
        $all_cards = $this->getAllCards();
        dd($all_cards);
    }

    private function dealWhiteCards() {

    }

    /**
     * Get all of the cards from the JSON file.
     * @return mixed
     */
    private function getAllCards() {
        return json_decode(file_get_contents(dirname(__DIR__) . $this->CARDS_FILE));
    }
}