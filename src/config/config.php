<?php

/**
 * This file is part of CaptainHook arrrrr.
 *
 * @license MIT
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Event listeners
    |--------------------------------------------------------------------------
    |
    | This array allows you to define all events that Captain Hook should
    | listen for in the application. By default, the Captain will just
    | respond to eloquent events, but you may edit this as you like.
    */
    'listeners' => ['eloquent.*'],

    /*
    |--------------------------------------------------------------------------
    | Webhook filter closure
    |--------------------------------------------------------------------------
    |
    | If your webhooks are scoped to a tenant_id, you can modify
    | this filter function to return only the webhooks for your
    | tenant. This function is applied as a collection filter.
    | The tenant_id field can be used for verification.
    |
    */
    'filter' => function ($webhook) {
        return true;
    },

    /*
    |--------------------------------------------------------------------------
    | Webhook data transformer
    |--------------------------------------------------------------------------
    |
    | The data transformer is a simple function that allows you to take the
    | subject data of an event and convert it to a format that will then
    | be posted to the webhooks. By default, all data is json encoded.
    | The second argument is the Webhook that was triggered in case
    | you want to transform the data in different ways per hook.
    |
    | You can also use the 'Foo\Class@transform' notation if you want.
    |
    */
    'transformer' => function ($eventData, $webhook) {
        return json_encode($eventData);
    },

    /*
    |--------------------------------------------------------------------------
    | Webhook response callback
    |--------------------------------------------------------------------------
    |
    | The response callback can be used if you want to trigger
    | certain actions depending on the webhook response.
    | This is unused by default.
    |
    | You can also use the 'Foo\Class@handle' notation if you want.
    |
    */
    'response_callback' => function ($webhook, $response) {
        // Handle custom response status codes, ...
    },

    /*
    |--------------------------------------------------------------------------
    | Logging configuration
    |--------------------------------------------------------------------------
    |
    | Captain Hook ships with built-in logging to allow you to store data
    | about the requests that you have made in a certain time interval.
    */
    'log' => [
        'active' => true,
        'storage_quantity' => 50,
    ],
];
