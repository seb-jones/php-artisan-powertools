<?php

namespace PhpArtisanPowertools;

use PhpArtisanPowertools\App\Console\Commands\CrudMakeCommand;
use PhpArtisanPowertools\App\Console\Commands\ReseedCommand;
use PhpArtisanPowertools\App\Console\Commands\RelateCommand;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\Blade;

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
        Blade::directive('dd', function ($expression) {
            return "<?php dd($expression); ?>";
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudMakeCommand::class,
                ReseedCommand::class,
                RelateCommand::class,
            ]);
        }
    }
}
