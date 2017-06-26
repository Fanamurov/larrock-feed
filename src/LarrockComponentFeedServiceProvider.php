<?php

namespace Larrock\ComponentFeed;

use Illuminate\Support\ServiceProvider;
use Larrock\Core\Middleware\AddSeofish;

class LarrockComponentFeedServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'larrock');

        $this->publishes([
            __DIR__.'/views' => base_path('resources/views/larrock'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app['router']->aliasMiddleware('AddSeofish', AddSeofish::class);
        $this->app->make(FeedComponent::class);

        if ( !class_exists('CreateLarrockMenuTable')){
            // Publish the migration
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/0000_00_00_000000_create_menu_table.php' => database_path('migrations/'.$timestamp.'_create_menu_table.php')
            ], 'migrations');
        }
    }
}
