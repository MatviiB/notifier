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
    public $routes;

    /**
     * @var
     */
    public $users;

    /**
     * Create a new event instance.
     *
     * @param $data
     * @param $routes
     * @param $users
     */
    public function __construct($data, $routes = false, $users = false)
    {
        $this->data = $data;
        $this->routes = $routes;
        $this->users = $users;
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
