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
    $router->get('today-complex', 'UserController@complexToday');

    $router->resource('advertisement', AdvertisementController::class);
    $router->post('advertisement/changeStatus', 'AdvertisementController@changeStatus');

    $router->resource('complex', ComplexController::class);

    $router->resource('withdraw', WithdrawController::class);
    $router->post('withdraw/changeStatus', 'WithdrawController@changeStatus');

    $router->resource('capital-pool', CapitalPoolController::class);

});
