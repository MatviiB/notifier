<?php

namespace MatviiB\Notifier\Commands;

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
    protected $headers = ['Method', 'Name', 'Middleware'];

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
        $this->routes = $router->getRoutes();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $routes = $this->getRoutes();

        if ($this->argument('show')) {
            $this->displayRoutes($routes);
        }

        $socket = $this->server->init();

        $connects = [];
        $per_pages = [];

        while (true) {
            $read = $connects;
            $read[] = $socket;
            $write = $except = [];

            if (!stream_select($read, $write, $except, null)) {
                break;
            }

            if (in_array($socket, $read) && ($connection = stream_socket_accept($socket, -1))) {

                $info = $this->server->handshake($connection);

                if ($info) {
                    if (isset($info['Socket-pass']) && $info['Socket-pass'] === config('notifier.socket_pass')) {
                        foreach ($connects as $key => $c) {
                            if (isset($per_pages[$key])) {
                                if (isset($info['Routes'])) {
                                    if (in_array($per_pages[$key], json_decode($info['Routes']))) {
                                        fwrite($c, $this->server->encode($info['Payload']));
                                    }
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

                $route = $this->server->decode($data)['payload'];

                if (in_array($route, array_column($routes, 'name'))) {
                    $connection_key = array_search($connection, $connects);
                    $per_pages[$connection_key] = $route;
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

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    protected function getRoutes()
    {
        $result = [];

        foreach ($this->routes as $route) {

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
     * @param  array  $routes
     * @return void
     */
    protected function displayRoutes(array $routes)
    {
        $this->table($this->headers, $routes);
    }

    /**
     * Get before filters.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getMiddleware($route)
    {
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->toArray();
    }
}