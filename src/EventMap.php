<?php

namespace MatviiB\Notifier;

trait EventMap
{
    /**
     * All of the Notifier event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        'MatviiB\Notifier\Events\Notify' => [
            'MatviiB\Notifier\Listeners\NotifyListener'
        ]
    ];
}