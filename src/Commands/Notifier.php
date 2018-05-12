<?php

namespace MatviiB\Notifier\Commands;

use Cache;
use Closure;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use MatviiB\Notifier\SocketServer;

class Notifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifier:init {show?}';

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
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * An array of all the registered routes.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    private $headers = ['Method', 'Name', 'Middleware'];

    /**
     * Socket connections.
     *
     * @var array
     */
    private $connects;

    /**
     * Connections to pages.
     *
     * @array
     */
    private $per_pages;

    /**
     * Connections to users.
     *
     * @array
     */
    private $per_users;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     */
    public function __construct(Router $router)
    {
        parent::__construct();

        $this->server = new SocketServer();
        $this->router = $router;
        $this->routes = $this->getRoutes();

        $this->connects = $this->per_pages = $this->per_users = [];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('show')) {
            $this->displayRoutes();
        }

        $socket = $this->server->init();

        while (true) {
            $read = $this->connects;
            $read[] = $socket;
            $write = $except = [];

            if (!stream_select($read, $write, $except, null)) {
                break;
            }

            if (in_array($socket, $read) && ($connection = stream_socket_accept($socket, -1))) {

                if ($info = $this->server->handshake($connection)) {
                    $this->computeIncomingInfo($info, $connection);
                }

                unset($read[array_search($socket, $read)]);
            }

            foreach ($read as $connection) {

                $data = fread($connection, 100000);

                if (!$data) {
                    $this->close($connection);
                    continue;
                }

                $connection_request = explode(':', $this->server->decode($data)['payload']);
                $route = $connection_request[0];

                if (isset($connection_request[1])) {
                    $unique_id = $connection_request[1];
                }

                if (in_array($route, array_column($this->routes, 'name'))) {
                    $connection_key = array_search($connection, $this->connects);
                    $this->per_pages[$connection_key] = $route;

                    if (isset($unique_id) && Cache::has('notifier:' . $unique_id)) {
                        $this->per_users[$connection_key] = Cache::get('notifier:' . $unique_id);
                    }
                } else {
                    $this->close($connection);
                    continue;
                }

                unset($route, $unique_id, $connection_request);
                //$this->server->onMessage($connect, $data);
            }
        }

        fclose($socket);
    }

    /**
     * @param $info
     * @param $connection
     */
    private function computeIncomingInfo($info, $connection)
    {
        if (isset($info['Socket-pass']) && $info['Socket-pass'] === config('notifier.socket_pass')) {
            $this->computeSystemMessage($info);
        } else {
            $this->connects[] =$connection;
            //$this->server->onOpen($connection, $info);
        }
    }

    /**
     * @param $info
     */
    private function computeSystemMessage($info)
    {
        foreach ($this->connects as $key => $connection) {
            if (isset($this->per_pages[$key])) {
                if (isset($info['Routes'])) {
                    $this->sendToRoutes($key, $connection, $info);
                } elseif (!isset($info['Route'])) {
                    fwrite($connection, $this->server->encode($info['Payload']));
                }
            }
        }
    }

    /**
     * @param $key
     * @param $connection
     * @param $info
     */
    private function sendToRoutes($key, $connection, $info)
    {
        if (in_array($this->per_pages[$key], json_decode($info['Routes']))) {
            if (isset($info['Users'])) {
                $this->sendToUsers($key, $connection, $info);
            } else {
                fwrite($connection, $this->server->encode($info['Payload']));
            }
        }
    }

    /**
     * @param $key
     * @param $connection
     * @param $info
     */
    private function sendToUsers($key, $connection, $info)
    {
        if (isset($this->per_users[$key]) && in_array($this->per_users[$key], json_decode($info['Users']))) {
            fwrite($connection, $this->server->encode($info['Payload']));
        }
    }

    /**
     * @param $connection
     */
    private function close($connection)
    {
        fclose($connection);
        $connection_key = array_search($connection, $this->connects);
        unset($this->connects[$connection_key]);
        unset($this->per_pages[$connection_key]);
        unset($this->per_users[$connection_key]);
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    private function getRoutes()
    {
        $result = [];

        foreach ($this->router->getRoutes() as $route) {

            $methods = $route->methods();
            if (!in_array('GET', $methods)) {
                continue;
            }

            $name = $route->getName();
            if (!$name) {
                continue;
            }

            $middleware = $this->getMiddleware($route);
            if (!in_array('web', $middleware)) {
                continue;
            }

            $result[] = [
                'method' => implode('|', $methods),
                'name'   => $name,
                'middleware' => implode(',', $middleware),
            ];
        }

        return $result;
    }

    /**
     * Display the route information on the console.
     *
     * @return void
     */
    private function displayRoutes()
    {
        $this->table($this->headers, $this->routes);
    }

    /**
     * Get before filters.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    private function getMiddleware($route)
    {
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->toArray();
    }
}