<?php

namespace App\Http\Controllers;

use App\SlackUser;
use Illuminate\Http\Request;

class SlackUserController extends Controller
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SlackUser  $slackUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SlackUser $slackUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SlackUser  $slackUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(SlackUser $slackUser)
    {
        //
    }
}
