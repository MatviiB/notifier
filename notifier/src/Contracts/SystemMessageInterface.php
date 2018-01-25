<?php

namespace MatviiB\Notifier\Contracts;

interface SystemMessageInterface
{
    /**
     * Send data from system to all connected listeners
     *
     * @param $data
     * @return mixed
     */
    public function send($data);

    /**
     * Create socket
     *
     * @return mixed
     */
    public function create();

    /**
     * Connect to socket
     *
     * @return mixed
     */
    public function connect();

    /**
     * Write to socket
     *
     * @param $data
     * @return mixed
     */
    public function write($data);

    /**
     * Close socket connection
     *
     * @return mixed
     */
    public function close();
}