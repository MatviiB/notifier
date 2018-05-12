<?php

namespace MatviiB\Notifier;

use MatviiB\Notifier\Middleware\InjectConnector;

use App\Http\Kernel;

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
        require_once('helper_connector.php');

        $events = $this->app->make(Dispatcher::class);

        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }

        $this->loadViewsFrom(__DIR__.'/views', 'notifier');

        if ($this->app->runningInConsole()) {

            $this->commands([
                Commands\Notifier::class,
            ]);

            $this->publishes([
                __DIR__ . '/config/notifier.php' => config_path('notifier.php'),
            ], 'config');
        }


    }
}