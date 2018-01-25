<?php

namespace MatviiB\Notifier;

trait Settings
{
    /**
     * Host for socket channel
     */
    protected $host;

    /**
     * Port for socket channel
     */
    protected $port;

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        $this->host = config('notifier.host');
        $this->port = config('notifier.port');
    }

    /**
     * Get headers message with given $data
     *
     * @param $data
     * @return string
     */
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

    /**
     * Accept (Upgrade) socket connection request
     *
     * @param $sec_web_socket_key
     * @return string
     */
    public function accept($sec_web_socket_key)
    {
        $sec_web_socket_accept = $this->getCode($sec_web_socket_key);

        return "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:" . $sec_web_socket_accept . "\r\n\r\n";
    }

    /**
     * Get code for validate socket connection
     *
     * @param $sec_web_socket_key
     * @return string
     */
    protected function getCode($sec_web_socket_key = false)
    {
        if ($sec_web_socket_key) {
            return $this->hash($sec_web_socket_key);
        }

        return $this->hash(str_random(16));
    }

    /**
     * Generate hash for socket key accepting
     *
     * @param $key
     * @return string
     */
    private function hash($key)
    {
        $const_hash = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

        return base64_encode(pack('H*', sha1($key . $const_hash)));
    }
}