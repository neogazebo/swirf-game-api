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
	$app->post('register', 'MemberController@register');
	$app->post('login', 'MemberController@login_app');
	$app->post('login/google', 'MemberController@login_google');
	$app->post('logout', [
	    'middleware' => 'Auth',
	    'uses' => 'MemberController@logout'
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
	$app->group(['prefix' => 'item'], function() use ($app) 
	{
	    $app->get('list', 'ItemController@listItem');
	});
    });


    
    //test
    $app->get('member/info/{id}', 'MemberController@getInfo');
});
