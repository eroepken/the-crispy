<?php

use Illuminate\Support\Facades\Route;

Route::post('/cah-game', 'CAHGameController@verifyAndStart');

// TODO: Implement these.
//Route::post('/cah-restart-round', 'CAHGameController@restartRound');
//Route::post('/cah-scores', 'CAHGameController@getScores');
//Route::post('/cah-my-score', 'CAHGameController@getMyScore');