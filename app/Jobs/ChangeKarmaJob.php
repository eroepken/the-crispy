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

    protected $table = 'karma_jobs';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $message_id, $recipient, $action)
    {
        $this->payload = [
          'message_id' => $message_id,
          'type' => $type,
          'recipient' => $recipient,
          'action' => $action,
        ];
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
          break;

        case 'thing':
          if (env('DEBUG_MODE')) {
            Log::debug('Calling thing handler.');
            Log::debug(print_r($this->job->payload(), true));
          }
          break;

        default:
          break;
      }

    }
}
