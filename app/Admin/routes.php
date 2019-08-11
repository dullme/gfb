<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->resource('users', UserController::class);
    $router->resource('service', ServiceController::class);
    $router->get('service-refresh-client-redis', 'ServiceController@refreshClientRedis');
    $router->get('edit-expiration', 'UserController@showEditExpiration');
    $router->post('edit-expiration', 'UserController@editExpiration');
    $router->get('add-days', 'UserController@showAddDays');
    $router->post('add-days', 'UserController@editAddDays');
    $router->post('users/changeStatus', 'UserController@changeStatus');
    $router->get('today-complex', 'UserController@complexToday');

    $router->resource('advertisement', AdvertisementController::class);
    $router->post('advertisement/changeStatus', 'AdvertisementController@changeStatus');

    $router->resource('complex', ComplexController::class);

    $router->resource('withdraw', WithdrawController::class);
    $router->post('withdraw/changeStatus', 'WithdrawController@changeStatus');

    $router->resource('capital-pool', CapitalPoolController::class);
    $router->resource('page', PageController::class);

});

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    Route::put('auth/setting', 'AuthController@putSetting');
});



