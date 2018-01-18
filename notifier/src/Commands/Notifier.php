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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

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

        $connects = array();

        while (true) {
            $read = $connects;
            $read [] = $socket;
            $write = $except = null;

            if (!stream_select($read, $write, $except, null)) {
                break;
            }

            if (in_array($socket, $read) && ($connect = stream_socket_accept($socket, -1))) {

                $info = $this->server->handshake($connect);

                if ($info) {
                    if (isset($info['Socket-pass']) && $info['Socket-pass'] === config('notifier.socket_pass')) {
                        foreach ($connects as $conn) {
                            fwrite($conn, $this->server->encode($info['Payload']));
                        }
                    } else {
                        $connects[] = $connect;
                        $this->server->onOpen($connect, $info);
                    }
                }

                unset($read[array_search($socket, $read)]);
            }

            foreach ($read as $connect) {
                $data = fread($connect, 100000);

                if (!$data) {
                    fclose($connect);
                    unset($connects[array_search($connect, $connects)]);
                    $this->server->onClose($connect);
                    continue;
                }

                $this->server->onMessage($connect, $data);
            }
        }

        fclose($socket);
    }
}