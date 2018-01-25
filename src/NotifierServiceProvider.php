<?php

namespace MatviiB\Notifier;

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
        $this->registerEvents();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\Notifier::class,
            ]);

            $this->publishes([
                __DIR__ . '/config/notifier.php' => config_path('notifier.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/resources/assets/js/notifier.js' => resource_path('assets/js/notifier/notifier.js')
            ], 'resources');

            $this->publishes([
                __DIR__ . '/resources/assets/js/notifier.js' => public_path('js/notifier/notifier.js')
            ], 'public');
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
}