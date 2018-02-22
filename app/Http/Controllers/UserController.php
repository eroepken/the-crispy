<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->command != '/birthday' && isset($request->token) && $request->token != config('services.slack.verification_token')) return response('false');

        $query_text = $request['text'];

        preg_match('/\@[\w\d\-\_]+/', $query_text, $matches);
        if (!empty($matches)) {
            $target_user = $matches[0];
            $birthday = str_replace($target_user, '', $query_text);
            $target_user = str_replace('@', '', $target_user);
        } else {
            $target_user = $request->user_name;
            $birthday = $query_text;
        }

        try {
            $birthday = new Carbon($birthday);
            $response_text = 'Your birthday has been logged.';
            Log::debug($birthday->format('F j'));
            Log::debug($birthday->age);
        } catch (Exception $exception) {
            $response_text = 'Please enter a valid date.';
            Log::error($exception->getMessage());
        }

        $response = [
            'response_type' => 'ephemeral',
            'user' => $target_user,
            'text' => $response_text,
        ];

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {

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
}
