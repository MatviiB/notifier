<?php

namespace MatviiB\Notifier\Contracts;

interface SocketServerInterface
{
    /**
     * Init socket server
     *
     * @return mixed
     */
    public function init();

    /**
     * Handshake - validate socket connection request
     *
     * @param $connect
     * @return mixed
     */
    public function handshake($connect);

    /**
     * Encode socket message
     *
     * @param $payload
     * @param string $type
     * @param bool $masked
     * @return mixed
     */
    public function encode($payload, $type = 'text', $masked = false);

    /**
     * Decode socket message
     *
     * @param $data
     * @return mixed
     */
    public function decode($data);

    /**
     * Action for "on open" new connection
     *
     * @param $connect
     * @param bool $info
     * @return mixed
     */
    public function onOpen($connect, $info = false);

    /**
     * Action for close connection
     *
     * @param $connect
     * @return mixed
     */
    public function onClose($connect);

    /**
     * Action for "on message" socket event
     *
     * @param $connect
     * @param $data
     * @return mixed
     */
    public function onMessage($connect, $data);
}