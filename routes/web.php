<?php

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
Route::match(['get', 'post'], '/crispy', function() {

    $type = request()->json('type');

    switch($type) {
        case 'url_verification':
            if (request()->json('token') != env('VERIFICATION_TOKEN')) {
                return response()->json(['text' => 'An error occurred.']);
            }

            return response()->json(['challenge' => request()->json('challenge')]);
            break;

        case 'message':
            if (!preg_match('/^Crispy/', request('text'))) {
                break;
            }
        case 'app_mention':
            $response = [
                'text' => 'You rang?',
                'ts' => request()->json('thread_ts')
            ];

            return response()->json($response);
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