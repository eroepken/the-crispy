<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KarmaController extends Controller
{
    public function parse($data) {
      Log::notice(print_r($data, true));
      Log::notice('HI.');
      error_log('This is the error log.');
    }

    private function add(User $user) {

    }

    private function substract(User $user) {

    }
}
