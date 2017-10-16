<?php

namespace Larrock\ComponentFeed;

use Illuminate\Support\ServiceProvider;
use Larrock\ComponentFeed\Middleware\AddSeofish;

class LarrockComponentFeedServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'larrock');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__.'/views' => base_path('resources/views/vendor/larrock')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('larrockfeed', function() {
            $class = config('larrock.components.feed', FeedComponent::class);
            return new $class;
        });

        $this->app['router']->aliasMiddleware('AddSeofish', AddSeofish::class);
    }
}