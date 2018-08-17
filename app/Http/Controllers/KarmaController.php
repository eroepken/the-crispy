<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KarmaController extends Controller
{
    public function parse($data) {
      Log::notice(print_r($data, true));
    }

    private function add(User $user) {

    }

    private function substract(User $user) {

    }
}
