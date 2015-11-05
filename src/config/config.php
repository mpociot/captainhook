<?php

/**
 * This file is part of CaptainHook arrrrr
 *
 * @license MIT
 */

return [

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
];
