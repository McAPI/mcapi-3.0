<?php

/**
 * Routes for resource index
 */
$router->get('/', 'IndexController@index');

/**
 * routes for resource game
 */
$router->group(['prefix' => 'game'], function () use ($router) {
    $router->get('/versions', 'GameController@versions');
});

/**
 * routes for resource user
 */
$router->group(['prefix' => 'user'], function () use ($router) {
    $router->get('/profile/{identifier}', 'UserController@information');
    $router->get('/reputation/{identifier}', 'UserController@reputation');
});

/**
 * routes for resource buycraft
 */
$router->group(['prefix' => 'buycraft'], function () use ($router) {

    $router->get('/information/{secret}', 'BuycraftController@information');

    $router->get('/category/listing/{secret}', 'BuycraftController@categoryListing');

    $router->get('/payment/listing/{secret}', 'BuycraftController@paymentListing');

    $router->get('/ban/listing/{secret}', 'BuycraftController@banListing');

    $router->get('/coupon/listing/{secret}', 'BuycraftController@couponListing');

    $router->group(['prefix' => 'command'], function () use ($router) {

        $router->get('queue/{secret}', 'BuycraftController@commandQueueListing');
        $router->get('queue/offline/{secret}', 'BuycraftController@commandOfflineQueueListing');

    });

});

/**
 * routes for resource server
 */
$router->group(['prefix' => 'server'], function () use ($router) {

    $router->get('/ping/{ip}[/{port}[/{version}]]', 'ServerController@ping');

});
