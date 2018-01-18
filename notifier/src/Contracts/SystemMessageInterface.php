<?php

namespace MatviiB\Notifier\Contracts;

interface SystemMessageInterface
{
    public function send($data);

    public function create();

    public function connect();

    public function write($data);

    public function close();
}