<?php

namespace MatviiB\Notifier;

use Illuminate\Http\Request;
use MatviiB\Notifier\Middleware\InjectConnector;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class NotifierServiceProvider extends ServiceProvider
{
    use EventMap;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {

            $this->registerEvents();

            $this->commands([
                Commands\Notifier::class,
            ]);

            $this->publishes([
                __DIR__ . '/config/notifier.php' => config_path('notifier.php'),
            ], 'config');
        } else {
            if (config('notifier.urls')) {

                $path = \request()->path();

                if ($path !== '/') {
                    $path = '/' . $path;
                }

                if (in_array($path, config('notifier.urls'))) {
                    $this->registerEvents();
                    $this->registerMiddleware(InjectConnector::class);
                }
            }
        }
    }

    /**
     * Register the Notifier job events.
     *
     * @return void
     */
    protected function registerEvents()
    {
        $events = $this->app->make(Dispatcher::class);

        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * Register the Notifier Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app[Kernel::class];
        $kernel->pushMiddleware($middleware);
    }
}