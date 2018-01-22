<?php

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\BotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Catch all for events.
Route::match(['get', 'post'], '/crispy', function(Request $request) {

    $event = $request = json_decode(request()->getContent(), true);

    if ($request['type'] == 'url_verification') {
        if ($request['token'] != env('VERIFICATION_TOKEN')) {
            return response()->json(['text' => 'An error occurred.']);
        }

        return response()->json(['challenge' => $event['challenge']]);
    } elseif ($request['type'] == 'event_callback') {
        $event = $request['event'];
    }

    Log::debug($request);

    switch($event['type']) {
        case 'message':
        case 'app_mention':
            if ($request['type'] == 'event_callback') {
                $response_ts = $event['event_ts'];
            } else {
                $response_ts = $event['thread_ts'];
            }

            $response = [
                'text' => 'You rang?',
                'channel' => $event['channel'],
                'ts' => $response_ts
            ];

            BotController::send($response);

            break;

        default:
            break;
    }

});

// Currently disabled.
/*Route::match(['get', 'post'], '/lmgtfy', function() {

    if (request('token') != env('VERIFICATION_TOKEN')) {
        return response()->json(['text' => 'An error occurred.']);
    }

    $query_text = request('text');

    preg_match('/\@[\w\d\-\_]+/', $query_text, $matches);
    if (!empty($matches)) {
        $target_user = $matches[0];
        $query_text = str_replace($target_user, '', $query_text);
    } else {
        $target_user = '@' . request('user_name');
    }

    $response = [
        'response_type' => 'in_channel',
        'text' => '<' . $target_user . '> http://lmgtfy.com/?q=' . urlencode(trim($query_text)),
    ];

    return response()->json($response);
});*/