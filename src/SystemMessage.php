<?php

namespace MatviiB\Notifier;

class SystemMessage
{
    /**
     * @var SocketServer
     */
    protected $server;

    /**
     * @var
     */
    protected $socket;

    /**
     * SystemMessage constructor.
     */
    public function __construct()
    {
        $this->server = new SocketServer();
    }

    /**
     * @param $data
     * @param bool $routes
     * @param bool $users
     */
    public function send($data, $routes = false, $users = false)
    {
        try {
            $this->create()->connect()->write($data, $routes, $users)->close();
        } catch (\Exception $e) {
            $error_code = socket_last_error();
            $error_msg = socket_strerror($error_code);

            \Log::error('Error while send system message', ['error' => $error_msg . "[$error_code]"]);
        }
    }

    /**
     * @return $this
     */
    private function create()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        return $this;
    }

    /**
     * @return $this
     */
    private function connect()
    {
        socket_connect($this->socket , $this->server->host, $this->server->port);

        return $this;
    }

    /**
     * @param $data
     * @param $routes
     * @param $users
     * @return $this
     */
    private function write($data, $routes, $users)
    {
        $message = $this->server->getMessage($data, $routes, $users);

        socket_send($this->socket, $message, strlen($message), 0);

        return $this;
    }

    /**
     * Close socket connection for this message.
     */
    private function close()
    {
        socket_close($this->socket);
    }
}