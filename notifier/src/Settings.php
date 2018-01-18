<?php

namespace MatviiB\Notifier;

trait Settings
{
    protected $host;

    protected $port;

    public function __construct()
    {
        $this->host = config('notifier.host');
        $this->port = config('notifier.port');
    }

    public function getMessage($data)
    {
        return "GET / HTTP/1.1\r\n" .
            "Host: " . $this->host . ":" . $this->port ."\r\n" .
            "Connection: Upgrade\r\n" .
            "Pragma: no-cache\r\n" .
            "Cache-Control: no-cache\r\n" .
            "Sec-WebSocket-Key: " . $this->getCode() . "\r\n" .
            "Socket-pass: ". config('notifier.socket_pass') . "\r\n" .
            "Payload: " . json_encode(['data' => $data]);
    }

    private function getCode()
    {
        return base64_encode(pack('H*', sha1(str_random(16) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    }
}