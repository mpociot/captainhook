<?php

namespace Mpociot\CaptainHook;

use Illuminate\Events\Dispatcher;

class EventDispatcher extends Dispatcher
{
    /**
      * The event firing stack.
      *
      * @var array
      */
    protected $firing = [];

    /**
      * Get the event that is currently firing.
      *
      * @return string
      */
    public function firing()
    {
        return last($this->firing);
    }

    /**
     * {@inheritdoc}
     */
    public function fire($event, $payload = [], $halt = false)
    {
        $this->firing[] = $event;
        $response = parent::fire($event, $payload);
        array_pop($this->firing);
        return $response;
    }
}
