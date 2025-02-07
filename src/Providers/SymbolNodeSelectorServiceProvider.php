<?php

namespace Ishidad2\SymbolNodeSelector\Providers;

use Illuminate\Support\ServiceProvider;

class SymbolNodeSelectorServiceProvider extends ServiceProvider
{
    /**
     * Register package components
     * Merges the package configuration into Laravel's config
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/symbolnodeselector.php', 'symbolnodeselector');
    }

    /**
     * Bootstrap package components
     * Publishes configuration file and defines config_path function if needed
     *
     * @return void
     */
    public function boot()
    {
        // Define config_path function if not in Laravel environment
        if (!function_exists('config_path')) {
            function config_path($path = '')
            {
                return dirname(__DIR__, 3) . '/config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
            }
        }

        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../../config/symbolnodeselector.php' => config_path('symbolnodeselector.php'),
        ], 'config');
    }
}