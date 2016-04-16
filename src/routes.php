<?php

Route::group(['middleware' => 'web'], function ($router) {
    $router->get('/settings/api/webhooks', 'Mpociot\CaptainHook\Http\WebhookController@all');
    $router->post('/settings/api/webhook', 'Mpociot\CaptainHook\Http\WebhookController@store');
    $router->put('/settings/api/webhook/{webhook_id}', 'Mpociot\CaptainHook\Http\WebhookController@update');
    $router->delete('/settings/api/webhook/{webhook_id}', 'Mpociot\CaptainHook\Http\WebhookController@destroy');

    $router->get('/settings/api/webhooks/events', 'Mpociot\CaptainHook\Http\WebhookEventsController@all');
});
