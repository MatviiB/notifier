<?php

namespace MatviiB\Notifier\Listeners;

use MatviiB\Notifier\Events\Notify;
use MatviiB\Notifier\SystemMessage;

class NotifyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  $event
     * @return void
     */
    public function handle(Notify $event)
    {
        with(new SystemMessage())->send($event->data, $event->routes, $event->users);
    }
}
