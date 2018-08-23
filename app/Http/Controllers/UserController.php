<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    // The year is irrelevant in this context, so make it consistent for querying purposes.
    const YEAR = 2000;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function upcomingBirthdays()
    {
        $today = Carbon::today();

        $start = $today;
        // Enforce the consistent year.
        $start->year = self::YEAR;

        // We don't want to bother users on the weekend.
        if ($today->isWeekend()) return response('false');

        if (Carbon::tomorrow()->dayOfWeek == Carbon::SATURDAY) {
            $end = new Carbon('this sunday');
            // Enforce the consistent year.
            $end->year = self::YEAR;

            $users = User::whereBetween('birthday', [$start->toDateString(), $end->toDateString()])->get()->toArray();
        } else {
            $users = User::whereDate('birthday', '=', $start->toDateString())->get()->toArray();
        }

        switch(count($users)) {
            case 0:
                return response('false');

            case 1:
                return response('Happy birthday, <@' . $users[0]['name'] . '>!');

            default:
                $user_list = [];

                foreach ($users as $user) {
                    $user_list[] = '<@' . $user['name'] . '>';
                }

                $last_user = array_pop($user_list);

                $user_list_string = implode(', ', $user_list);
                if (count($user_list) > 1) {
                    $user_list_string .= ',';
                }
                $user_list_string .= ' and ' . $last_user;

                return response('Happy birthday to ' . $user_list_string . '!');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeOrUpdate(Request $request)
    {
        if ($request->command != '/birthday' && isset($request->token) && $request->token != config('services.slack.verification_token')) return response('false');

        try {
            $birthday = new Carbon($request->text);
            // The year is irrelevant in this context, so make it consistent for querying purposes.
            $birthday->year = self::YEAR;
            $response_text = 'Sweet. I will remember your birthday from this point on. :wink: :birthday:';

            $user = User::firstOrNew(['slack_id' => $request->user_id]);
            $user->slack_id = $request->user_id;
            $user->name = $request->user_name;
            $user->birthday = $birthday;
            $user->save();
        } catch (Exception $exception) {
            $response_text = 'Oh no! Either you supplied an invalid date or something went wrong with the bot.';
            Log::error($exception->getMessage());
        }

        $response = [
            'response_type' => 'ephemeral',
            'user' => $request->user_id,
            'text' => $response_text,
        ];

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function list() {
        $users = User::select('name', 'karma')->orderBy('karma', 'desc')->get();
        $things = DB::table('things')->select('name', 'karma')->orderBy('karma', 'desc')->get();

        $emoji_data = collect(json_decode(file_get_contents(dirname(__DIR__, 3) . '/database/emoji/emoji-data.json')));

        $things_with_emoji = $things->filter(function($val, $key) {
            return preg_match('/:\w+?:/', $val->name);
        });

        $things_with_emoji->map(function($thing, $key) use ($emoji_data, &$things) {
            if (preg_match('/:?\w+?:?/', $thing->name)) {
                $name = str_replace(':', '', $thing->name);
                if($emoji_data->contains('short_name', $name)) {
                    $emoji = $emoji_data->firstWhere('short_name', $name);
                    $things[$key]->name = $GLOBALS['emoji_maps']['html_to_unified'][strtolower($emoji->unified)];
                }
            }
        });

        return view('leaderboard', compact('users', 'things'));
    }

  /**
   * Generic get top users function.
   * @param $num
   *
   * @return mixed
   */
    public static function getTop($num) {
        return User::select('name', 'karma')->orderBy('karma', 'desc')->limit($num)->get();
    }

    /**
     * Select and format the top users for display in the channel.
     *
     * @param int $num The number of users to grab for the "top" listing.
     */
    public static function getTopFormatted($num = 10) {
        if ($num <= 0) {
          return 'You must supply a positive number to view the top users.';
        }

        $users = static::getTop($num);

        $users[0]->name += ' :high_heel:';

        $formatted_list = '';
        foreach ($users as $key => $user) {
          $i = $key + 1;
          $formatted_list .= "$i. $user->name &mdash; $user->karma";
        }

        return $formatted_list;
    }
}
