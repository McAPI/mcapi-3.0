<?php

/**
 * Routes for resource index
 */
$router->get('/', 'IndexController@index');


/**
 * routes for resource game
 */
$router->get('/game', 'GameController@index');