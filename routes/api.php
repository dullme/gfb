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

/**
 * 需要 OAuth 认证
 */
Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/user-info', 'UserController@updateUserInfo');
    Route::get('/user-info', 'UserController@userInfo');
    Route::get('/complex', 'UserController@complex');
    Route::get('/image', 'UserController@getImage');
    Route::get('/withdraw', 'UserController@getWithdraw');
    Route::post('/withdraw', 'UserController@storeWithdraw');
    Route::post('/avatar-upload', 'UserController@avatarUpload');
});

