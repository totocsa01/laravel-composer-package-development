<?php

namespace Totocsa01\ComposerPackageDevelopment;

use Illuminate\Support\ServiceProvider;

class ComposerPackageDevelopmentServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Totocsa01\ComposerPackageDevelopment\app\Console\Commands\ComposerPackageTypePathOn::class,
                \Totocsa01\ComposerPackageDevelopment\app\Console\Commands\ComposerPackageTypePathOff::class,
            ]);
        }
    }
}
