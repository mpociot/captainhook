<?php

namespace Mpociot\CaptainHook\Http;

use Illuminate\Http\Request;
use Laravel\Spark\Http\Controllers\Controller;

class WebhookEventsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get all of the available webhook events.
     *
     * @return Response
     */
    public function all(Request $request)
    {
        return collect(config('captain_hook.listeners', []))->transform(function ($key, $value) {
            return [
                'name' => $value,
                'event' => $key,
            ];
        })->values();
    }
}
