<?php

namespace PhpArtisanPowertools;

use PhpArtisanPowertools\App\Console\Commands\CrudMakeCommand;
use PhpArtisanPowertools\App\Console\Commands\PivotMakeCommand;
use PhpArtisanPowertools\App\Console\Commands\ReseedCommand;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudMakeCommand::class,
                PivotMakeCommand::class,
                ReseedCommand::class,
            ]);
        }
    }
}
