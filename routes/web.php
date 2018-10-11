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
    $users = \App\Models\User::where('status', 2)->get();
    foreach ($users as $user){
        Redis::incrby('v_'.$user->id.'_20181010', 1);
        Redis::incrby('a_'.$user->id.'_20181010', 1);
    }

    return view('welcome');
});
