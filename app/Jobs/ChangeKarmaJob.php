<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ChangeKarmaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $type = '';
    private $event_data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $event_data)
    {
        $this->message_id = $event_data['client_msg_id'];
        $this->type = $type;
        $this->payload = $event_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

      switch ($this->type) {
        case 'user':
          $this->userHandler();
          break;

        case 'thing':
          $this->thingHandler();
          break;

        default:
          break;
      }

    }

    /**
     * Handle the karma for users.
     */
    private function userHandler() {
        if (env('DEBUG_MODE')) {
            Log::debug('Receiving from Slack:' . print_r($this->event_data, true));
        }

        foreach($matches[1] as $i => $rec) {
            $user = User::firstOrNew(['slack_id' => $rec]);

            if (env('DEBUG_MODE')) {
                Log::debug('User:' . print_r($user, true));
            }

            if (!$user->exists || empty($user)) {
                $user = new User();
                $user->slack_id = $rec;
                $user->karma = 0;
            }

            if ($rec === $this->event_data['user']) {
                $user->save();
                $bot->reply('You can\'t change your own karma! <@' . $user->slack_id . '> still at ' . $user->karma . ' points.');
                continue;
            }

            $action = $matches[2][$i];

            switch($action) {
                case '++':
                    $user->addKarma();
                    if ($user->slack_id === env('BOT_UID')) {
                      $bot->addReactions(SlackBot::pickReactionsFromList(SlackBot::YAY_REACTIONS, 2));
                    }
                    break;

                case '--':
                    $user->subtractKarma();
                    if ($user->slack_id === env('BOT_UID')) {
                      $bot->addReactions(SlackBot::pickReactionsFromList(SlackBot::FU_REACTIONS, 2));
                    }
                    break;

                default:
                    break;
            }

            $user->save();
            $bot->reply('<@' . $user->slack_id . '> now has ' . $user->karma . ' ' . (abs($user->karma) === 1 ? 'point' : 'points') . '.');
        }
    }

    /**
     * Handle the karma logic for things.
     */
    private function thingHandler() {
        if (env('DEBUG_MODE')) {
            Log::debug('Receiving from Slack:' . print_r($event_data, true));
        }

        $existing_things = DB::table('things')->select('name', 'karma')->whereIn('name', '=', $matches[1])->get();

        if (env('DEBUG_MODE')) {
            Log::debug('Things:' . print_r($existing_things, true));
        }

        foreach($matches[1] as $i => $rec) {
            $action = $matches[2][$i];

            // Create a new record if it doesn't exist.
            if (!$existing_things->contains('name', $rec)) {
                DB::table('things')->insert(['name' => $rec, 'karma' => 0]);
            }

            switch($action) {
                case '++':
                    DB::table('things')->where('name', '=', $rec)->increment('karma');
                    break;

                case '--':
                    DB::table('things')->where('name', '=', $rec)->decrement('karma');
                    break;

                default:
                    break;
            }

            $updated = DB::table('things')->select('karma')->where('name', $matches[1])->get()->first();
            $bot->reply('@' . $rec . ' now has ' . $updated->karma . ' ' . (abs($updated->karma) === 1 ? 'point' : 'points') . '.');
        }
    }
}
