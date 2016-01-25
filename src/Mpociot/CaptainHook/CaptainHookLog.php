<?php
namespace Mpociot\CaptainHook;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * This file is part of CaptainHook arrrrr
 *
 * @license MIT
 * @package CaptainHook
 */
class CaptainHookLog extends Eloquent
{
    /**
     * Make the fields fillable.
     *
     * @var array
     */
    protected $fillable = ['webhook_id', 'url', 'payload_format', 'payload', 'status', 'response', 'response_format'];
}
