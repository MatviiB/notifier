<?php

namespace MatviiB\Notifier\Contracts;

interface SocketServerInterface
{
    public function init();

    public function handshake($connect);

    public function encode($payload, $type = 'text', $masked = false);

    public function decode($data);


    public function onOpen($connect, $info = false);

    public function onClose($connect);

    public function onMessage($connect, $data);
}