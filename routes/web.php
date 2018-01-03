<?php

/**
 * Routes for resource index
 */
$router->get('/', 'IndexController@index');


/**
 * routes for resource game
 */
$router->get('/game/versions', 'GameController@versions');

/**
 * routes for resource user
 */
$router->get('/user/{identifier}', 'UserController@information');
$router->get('/user/{identifier}/reputation', 'UserController@reputation');
