<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->group(['prefix' => 'v1', 'namespace' => 'V1'], function() use ($app) {
    //Auth endpoint
    $app->group(['prefix' => 'auth'], function() use ($app) {
	$app->post('register', 'AuthController@register');
	$app->post('login', 'AuthController@login');
	$app->post('login/google', 'AuthController@loginGoogle');
	$app->post('logout', [
	    'middleware' => 'Auth',
	    'uses' => 'AuthController@logout'
	]);
    });

    //Admin webhooks
    $app->group(['prefix' => 'webhook'], function() use ($app) {
	$app->post('/profile', 'AdminWebhookController@profile');
    });

    $app->group(['prefix' => 'device'], function() use ($app) {
	$app->post('register', 'DeviceController@register');
	$app->post('check', 'DeviceController@securityCheck');
    });

    //all endpoint that need token
    $app->group(['middleware' => 'token'], function() use ($app) {

	//Item
	$app->group(['prefix' => 'item'], function() use ($app) {
	    $app->get('list', 'ItemController@listItem');
	    $app->get('collected', 'ItemController@collectedItem');
	    $app->post('grab', 'ItemController@grabItem');
	});
	
	//Reward
	$app->group(['prefix' => 'reward'], function() use ($app) {
	    $app->get('list', 'RewardController@listAll');
//	    $app->get('collected', 'ItemController@collectedItem');
//	    $app->post('grab', 'ItemController@grabItem');
	});
    });
});
