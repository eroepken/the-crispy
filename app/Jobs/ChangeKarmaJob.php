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
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

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
          $user = User::firstOrNew(['slack_id' => $this->recipient]);
          if (!$user->exists) {
            $user->slack_id = $rec;
            $user->karma = 0;
          }

          switch($this->action) {
            case '++':
              $user->addKarma();
              break;

            case '--':
              $user->subtractKarma();
              break;

            default:
              break;
          }

//          $replies[$user->slack_id] = '<@' . $user->slack_id . '> now has ' . $user->karma . ' ' . (abs($user->karma) === 1 ? 'point' : 'points') . '.';
          break;

        case 'thing':
          $existing_things = DB::table('things')->select('name', 'karma')->where('name', $this->recipient)->get();

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
