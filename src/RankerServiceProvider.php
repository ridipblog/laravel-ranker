<?php

namespace Ranker;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ranker\Http\Controllers\RankerController;
use Ranker\Services\RankerManager;

class RankerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ranker.php',
            'ranker'
        );

        $this->app->singleton('laravel-ranker', function ($app) {
            return new RankerManager();
        });

        $this->app->alias('laravel-ranker', RankerManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ranker.php' => config_path('ranker.php'),
            ], 'ranker-config');
        }

        $this->registerRouteMacros();
    }

    /**
     * Register route macro for Ranker reorder endpoint.
     */
    protected function registerRouteMacros(): void
    {
        Route::macro('ranker', function (string $uri = 'ranker/reorder', array|string $action = [RankerController::class, 'reorder']) {
            /** @var \Illuminate\Routing\Router $this */
            return $this->post($uri, $action);
        });
    }
}
