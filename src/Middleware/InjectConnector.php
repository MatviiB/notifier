<?php

namespace MatviiB\Notifier\Middleware;

use Error;
use Closure;
use Exception;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\Exception\FatalThrowableError;


class InjectConnector
{
    /**
     * The App container
     *
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
//        dd($request->path());
        try {
            /** @var \Illuminate\Http\Response $response */
            $response = $next($request);
        } catch (Exception $e) {
            $response = $this->handleException($request, $e);
        } catch (Error $error) {
            $e = new FatalThrowableError($error);
            $response = $this->handleException($request, $e);
        }

        $this->modifyResponse($response);

        return $response;

    }

    /**
     * Handle the given exception.
     *
     * (Copy from Illuminate\Routing\Pipeline by Taylor Otwell)
     *
     * @param $passable
     * @param  Exception $e
     * @return mixed
     * @throws Exception
     */
    protected function handleException($passable, Exception $e)
    {
        if (! $this->container->bound(ExceptionHandler::class) || ! $passable instanceof Request) {
            throw $e;
        }

        $handler = $this->container->make(ExceptionHandler::class);

        $handler->report($e);

        return $handler->render($passable, $e);
    }

    /**
     * Modify the response
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function modifyResponse(Response $response)
    {
        try {

            $content = $response->getContent();

            $pos = strripos($content, '</head>');

            if (false !== $pos) {
                $content = substr($content, 0, $pos) . $this->renderJS() . substr($content, $pos);
            } else {
                $content = $content . $this->renderJS();
            }

//          Update the new content and reset the content length
            $response->setContent($content);
            $response->headers->remove('Content-Length');
        } catch (\Exception $e) {
            app(['log'])->error('Notifier exception: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Render js connection to Socket server.
     *
     * @return string
     */
    private function renderJS()
    {
        return '<script>var host = \'' . config('notifier.host') . '\';' .
            'var port = \'' . config('notifier.port') . '\';' .
            'var socket = new WebSocket(\'' . config('notifier.connection') . '://\' + host + \':\' + port);' .
            'socket.onopen = function(e) {' .
            'socket.send(location.pathname);' .
            'console.log("Connection established!");' .
            '};' .
            '</script>' . PHP_EOL;
    }
}