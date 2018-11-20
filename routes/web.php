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

Route::get('/', 'RouteController@index');

Route::get('download/guafen.apk', 'RouteController@download');
Route::get('delete', 'RouteController@delete');
Route::get('clear-redis', 'RouteController@byUserIdClearUserInLocalRedis');
Route::get('update-redis', 'RouteController@byUserIdUpdateUserInLocalRedis');
Route::get('clear-config', 'RouteController@ClearConfigInLocalRedis');
Route::get('update-main-redis', 'RouteController@updateMainRedis');
Route::get('add-user-validity-period', 'RouteController@addUserValidityPeriod');

//Route::get('doing', 'RouteController@doing');
