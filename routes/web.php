<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

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

Route::post('/birthday', function(Request $request) {
    $request = $request->all();

    if ($request['command'] != '/birthday' && isset($request['token']) && $request['token'] != config('services.slack.verification_token')) return response('false');

    $query_text = $request['text'];

    preg_match('/\@[\w\d\-\_]+/', $query_text, $matches);
    if (!empty($matches)) {
        $target_user = $matches[0];
        $birthday = str_replace($target_user, '', $query_text);
    } else {
        $target_user = '@' . $request['user_name'];
        $birthday = $query_text;
    }

    try {
        $birthday = new Carbon($birthday);
        $response_text = 'Your birthday has been logged.';
        Log::debug($birthday->format('F j'));
        Log::debug($birthday->age);
    } catch (\Carbon\Exceptions\InvalidDateException $dateException) {
        Log::error($dateException->getMessage());
        $response_text = $dateException->getMessage();
    }

    $response = [
        'response_type' => 'ephemeral',
        'user' => $target_user,
        'text' => $response_text,
    ];

    return response()->json($response);
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