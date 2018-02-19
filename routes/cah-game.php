<?php

use Illuminate\Support\Facades\Route;

Route::post('/cah-game', 'CAHGameController@verifyAndStart');
Route::post('/cah-cards', 'CAHGameController@getCardData');

// TODO: Implement these.
//Route::post('/cah-restart-round', 'CAHGameController@restartRound');
//Route::post('/cah-scores', 'CAHGameController@getScores');
//Route::post('/cah-my-score', 'CAHGameController@getMyScore');