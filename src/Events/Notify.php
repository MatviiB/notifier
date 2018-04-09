<?php

namespace MatviiB\Notifier\Events;


class Notify
{

    /**
     * @var
     */
    public $data;

    /**
     * @var
     */
    public $route;

    /**
     * Create a new event instance.
     *
     * @param $data
     * @param $route
     */
    public function __construct($data, $route = false)
    {
        $this->data = $data;
        $this->route = $route;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        //
    }
}
