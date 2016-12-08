<?php

namespace Mpociot\CaptainHook;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * This file is part of CaptainHook arrrrr.
 *
 * @property integer id
 * @property integer tenant_id
 * @property string  event
 * @property string  url
 * @license MIT
 */
class Webhook extends Eloquent
{
    /**
     * Cache key to use to store loaded webhooks.
     */
    const CACHE_KEY = 'mpociot.captainhook.hooks';

    /**
     * Make all fields fillable.
     * @var array
     */
    public $fillable = ['id', 'url', 'event', 'tenant_id'];

    /**
     * Boot the model
     * Whenever a new Webhook get's created the cache get's cleared.
     */
    public static function boot()
    {
        parent::boot();

        static::created(function ($results) {
            Cache::forget(self::CACHE_KEY);
        });

        static::updated(function ($results) {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function ($results) {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * Retrieve the logs for a given hook.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }

    /**
     * Retrieve the logs for a given hook.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lastLog()
    {
        return $this->hasOne(WebhookLog::class)->orderBy('created_at', 'DESC');
    }
}
