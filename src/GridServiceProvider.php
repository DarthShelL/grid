<?php

namespace DarthShelL\Grid;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GridServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig();
    }

    private function bootConfig() {
        $this->loadViewsFrom(__DIR__.'/views', 'grid');

        $this->publishes([
            __DIR__.'/views' => base_path('resources/views/grid'),
            __DIR__.'/css' => base_path('public/css/grid'),
            __DIR__.'/js' => base_path('public/js/grid'),
        ]);
    }
}
