<?php

namespace Mpociot\CaptainHook;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * This file is part of CaptainHook arrrrr.
 *
 * @license MIT
 */
class WebhookLog extends Eloquent
{
    /**
     * Make the fields fillable.
     *
     * @var array
     */
    protected $fillable = ['webhook_id', 'url', 'payload_format', 'payload', 'status', 'response', 'response_format'];

    /**
     * Retrieve the webhook described by the log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
