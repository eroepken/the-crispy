<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\User;

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
    if ($request->command != '/birthday' && isset($request->token) && $request->token != config('services.slack.verification_token')) return response('false');

    try {
        $birthday = new Carbon($request->text);
        $response_text = 'Your birthday has been logged.';

        $user = User::firstOrNew(array('slack_id' => $request->user_id));
        $user->slack_id = $request->user_id;
        $user->name = $request->user_name;
        $user->birthday = $birthday;
        $user->save();
    } catch (Exception $exception) {
        $response_text = 'Please enter a valid date.';
        Log::error($exception->getMessage());
    }

    $response = [
        'response_type' => 'ephemeral',
        'user' => $request->user_id,
        'text' => $response_text,
    ];

    return response()->json($response);
});
Route::post('/birthday/remove', 'UserController@destroy');

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