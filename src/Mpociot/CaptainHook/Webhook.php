<?php
namespace Mpociot\CaptainHook;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Cache;

/**
 * This file is part of CaptainHook arrrrr
 *
 * @license MIT
 * @package CaptainHook
 */
class Webhook extends Eloquent
{
    /**
     * Cache key to use to store loaded webhooks
     */
    const CACHE_KEY = 'mpociot.captainhook.hooks';

    /**
     * Make all fields fillable
     * @var array
     */
    public $fillable = ['id', 'url', 'event', 'tenant_id'];


    /**
     * Boot the model
     * Whenever a new Webhook get's created the cache get's cleared
     */
    public static function boot()
    {
        parent::boot();

        static::created(function ($results) {
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
}
