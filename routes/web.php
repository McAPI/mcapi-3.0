<?php

$router->get('/', 'IndexController@index');

$router->group(['prefix' => 'api'], function () use ($router) {

    /**
     * Routes for resource index
     */
    $router->get('/', 'IndexController@apiIndex');

    /**
     * routes for resource game
     */
    $router->group(['prefix' => 'game'], function () use ($router) {

        $router->group(['prefix' => 'versions'], function () use ($router) {
            $router->get('/', 'GameController@versions');
            $router->get('/run/{version}', 'GameController@versionRun');
        });

        $router->group(['prefix' => 'services'], function () use ($router) {
            $router->get('/status/{service}', 'GameController@servicesStatus');
            $router->get('/status', 'GameController@servicesStatusBatch');
        });


    });

    /**
     * routes for resource user
     */
    $router->group(['prefix' => 'user'], function () use ($router) {

        //------------------ @LEGACY ---------------------------
        $router->get('/{identifier}', 'UserController@information');
        $router->get('/{identifier}/reputation', 'UserController@reputation');
        //------------------ @LEGACY ---------------------------

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

        $router->group(['prefix' => 'giftcard'], function () use ($router) {

            $router->get('listing/{secret}', 'BuycraftController@giftcardListing');
            $router->get('listing/{secret}/{id}', 'BuyCraftController@giftcardShowCard');

        });


        $router->group(['prefix' => 'coupon'], function () use ($router) {

            $router->get('listing/{secret}', 'BuycraftController@couponListing');
            $router->get('listing/{secret}/{id}', 'BuyCraftController@couponShowCoupon');

        });

        $router->group(['prefix' => 'command'], function () use ($router) {

            $router->get('queue/{secret}', 'BuycraftController@commandQueueListing');
            $router->get('queue/offline/{secret}', 'BuycraftController@commandOfflineQueueListing');
            $router->get('queue/online/{secret}/{playerid}', 'BuycraftController@commandOnlineQueueListing');

        });

    });

    /**
     * routes for resource server
     */
    $router->group(['prefix' => 'server'], function () use ($router) {

        $router->get('/ping/{ip}[/{port}]', 'ServerController@ping');
        $router->get('/query/{ip}[/{port}]', 'ServerController@query');

        $router->group(['prefix' => 'pe'], function () use ($router) {

            $router->get('ping/{ip}', 'ServerController@pePing');

        });

    });

    /**
     * routes for resource image
     */
    $router->group(['prefix' => 'image'], function () use ($router) {

        $router->get('/favicon/{ip}[/{port}]', 'ImageController@favicon');

    });

    /**
     * routes for voting
     */
    $router->group(['prefix' => 'voting'], function () use ($router) {

        $router->get('/nu/{ip}/{port}/{identifier}/{token}/{publicKey}', 'VotingController@nuVotifier');

    });


});