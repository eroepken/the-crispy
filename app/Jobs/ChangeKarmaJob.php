<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Bots\SlackBot;
use App\User;

class ChangeKarmaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message_id;
    public $type;
    public $recipient;
    public $action;

    protected $table = 'karma_jobs';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $message_id, $recipient, $action)
    {
        $this->message_id = $message_id;
        $this->type = $type;
        $this->recipient = $recipient;
        $this->action = $action;

        $this->onQueue('karma');
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
          if (env('DEBUG_MODE')) {
            Log::debug('Calling user handler.');
            Log::debug(print_r($this->job->payload(), true));
          }

          $user = User::firstOrNew(['slack_id' => $this->recipient]);
          if (!$user->exists) {
            $user->slack_id = $rec;
            $user->karma = 0;
          }

          switch($this->action) {
            case '++':
              $user->karma++;
//              if ($user->slack_id === env('BOT_UID')) {
//                $bot->addReactions(SlackBot::pickReactionsFromList(SlackBot::YAY_REACTIONS, 2));
//              }
              break;

            case '--':
              $user->karma--;
//              if ($user->slack_id === env('BOT_UID')) {
//                $bot->addReactions(SlackBot::pickReactionsFromList(SlackBot::FU_REACTIONS, 2));
//              }
              break;

            default:
              break;
          }

          $user->save();
//          $replies[$user->slack_id] = '<@' . $user->slack_id . '> now has ' . $user->karma . ' ' . (abs($user->karma) === 1 ? 'point' : 'points') . '.';
          break;

        case 'thing':
          $existing_things = DB::table('things')->select('name', 'karma')->where('name', $this->recipient)->get();

          if (env('DEBUG_MODE')) {
            Log::debug('Calling thing handler.');
            Log::debug('Existing thing: ' . print_r($existing_things, true));
          }

          // Create a new record if it doesn't exist.
          if (!$existing_things->contains('name', $this->recipient)) {
            DB::table('things')->insert(['name' => $this->recipient, 'karma' => 0]);
          }

          switch($this->action) {
            case '++':
              DB::table('things')->where('name', '=', $this->recipient)->increment('karma');
              break;

            case '--':
              DB::table('things')->where('name', '=', $this->recipient)->decrement('karma');
              break;

            default:
              break;
          }

//          $updated = DB::table('things')->select('karma')->where('name', $this->recipient)->get()->first();
//          $replies[$rec] = '@' . $rec . ' now has ' . $updated->karma . ' ' . (abs($updated->karma) === 1 ? 'point' : 'points') . '.';
          break;

        default:
          break;
      }

    }
}
