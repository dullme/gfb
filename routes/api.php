<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


//用户登录
Route::post('/login', 'LoginController@userLogin');

//退出登录
Route::post('/logout', 'LoginController@userLogout');

//重置Token
Route::post('/token/refresh', 'LoginController@refresh');

