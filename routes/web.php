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
    Redis::incrby('v_100024_'.date('Ymd'), 1);
    Redis::incrby('v_100023_'.date('Ymd'), 1);
    Redis::incrby('a_100023_'.date('Ymd'),333);
    Redis::incrby('a_100024_'.date('Ymd'),987);
    return view('welcome');
});
