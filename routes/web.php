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

$app->group(['prefix' => 'v1', 'namespace' => 'V1'], function() use ($app)
{
    $app->post('device/register', 'DeviceController@register');

    $app->post('member/register', 'MemberController@register');

    $app->post('member/login_app', 'MemberController@login_app');

    $app->post('member/login_google', 'MemberController@login_google');

    $app->post('member/logout', 'MemberController@logout');

    // TEST
    $app->get('member/get_info', [
        'middleware' => 'Auth',
        'uses'       => 'MemberController@get_info'
    ]);
});
