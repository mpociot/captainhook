<?php namespace Mpociot\CaptainHook;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model as Eloquent;

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
    const CACHE_KEY = "mpociot.captainhook.hooks";

    /**
     * Make all fields fillable
     * @var array
     */
    public $fillable = ['id', 'url', 'event'];


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
}