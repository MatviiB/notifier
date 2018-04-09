<?php

namespace MatviiB\Notifier\Commands;

use MatviiB\Notifier\SocketServer;

use Illuminate\Console\Command;

class Notifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifier:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init socket server';

    /**
     * Socket server instance
     *
     * @var
     */
    protected $server;

    /**
     * @var
     */
    public $urls;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->urls = config('notifier.urls');
        $this->server = new SocketServer();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $socket = $this->server->init();

        $connects = [];
        $per_pages = [];

        while (true) {
            $read = $connects;
            $read[] = $socket;
            $write = $except = null;

            if (!stream_select($read, $write, $except, null)) {
                break;
            }

            if (in_array($socket, $read) && ($connection = stream_socket_accept($socket, -1))) {

                $info = $this->server->handshake($connection);

                if ($info) {
                    if (isset($info['Socket-pass']) && $info['Socket-pass'] === config('notifier.socket_pass')) {
                        foreach ($connects as $key => $c) {
                            if (isset($per_pages[$key])) {
                                if (isset($info['Route']) && ($per_pages[$key] == $info['Route'])) {
                                    fwrite($c, $this->server->encode($info['Payload']));
                                } elseif (!isset($info['Route'])) {
                                    fwrite($c, $this->server->encode($info['Payload']));
                                }
                            }
                        }
                    } else {
                        $connects[] = $connection;
//                        $this->server->onOpen($connection, $info);
                    }
                }

                unset($read[array_search($socket, $read)]);
            }

            foreach ($read as $connection) {

                $data = fread($connection, 100000);

                if (!$data) {
                    fclose($connection);
                    $connection_key = array_search($connection, $connects);
                    unset($connects[$connection_key]);
                    unset($per_pages[$connection_key]);
                    continue;
                }

                $url = $this->server->decode($data)['payload'];

                if (in_array($url, $this->urls)) {
                    $connection_key = array_search($connection, $connects);
                    $per_pages[$connection_key] = $url;
                } else {
                    fclose($connection);
                    $connection_key = array_search($connection, $connects);
                    unset($connects[$connection_key]);
                    unset($per_pages[$connection_key]);
                    continue;
                }
//                $this->server->onMessage($connect, $data);
            }
        }

        fclose($socket);
    }
}