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

//初始化接口
Route::get('/system-info', 'LoginController@systemInfo');

//淘宝任务
Route::get('/task', 'LoginController@task');

/**
 * 需要 OAuth 认证
 */
Route::post('/user-info', 'UserController@updateUserInfo'); //账号激活
Route::get('/user-info', 'UserController@userInfo'); //获取用户信息
Route::get('/complex', 'UserController@complex'); //分润记录
Route::get('/withdraw', 'UserController@getWithdraw'); //提现信息
Route::post('/withdraw', 'UserController@storeWithdraw'); //提现接口
Route::get('/can-see', 'UserController@canSeeAd'); //是否可以访问广告
Route::get('/image', 'ProfitController@getImage'); //获取广告
//Route::get('/get-image', 'ProfitController@getMobileImage');
