<?php

namespace MatviiB\Notifier;

class SystemMessage
{
    use Settings;

    protected $socket;

    public function send($data, $route = false)
    {
        try {
            $this->create()->connect()->write($data, $route)->close();
        } catch (\Exception $e) {
            $error_code = socket_last_error();
            $error_msg = socket_strerror($error_code);

            \Log::error('Error while send system message', ['error' => $error_msg . "[$error_code]"]);
        }
    }

    private function create()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        return $this;
    }

    private function connect()
    {
        socket_connect($this->socket , $this->host, $this->port);

        return $this;
    }

    private function write($data, $route)
    {
        $message = $this->getMessage($data, $route);

        socket_send($this->socket, $message, strlen($message), 0);

        return $this;
    }

    private function close()
    {
        socket_close($this->socket);
    }
}