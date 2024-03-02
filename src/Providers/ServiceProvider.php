<?php

namespace Zatara\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Zatara\Support\Zatara;
use Zatara\Support\Facades\Zatara as ZataraFacade;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->singleton('zatara', fn (Application $app) => new Zatara($app));
    }

    public function boot()
    {
        Route::middleware('web')->group(function () {
            ZataraFacade::actions()->each(fn ($action) => (
                Route::match(
                    [$action->route->method],
                    $action->route->uri,
                    $action->classname
                )
                    ->name($action->route->name)
                    ->middleware($action->route->middleware)
            ));
        });
    }
}
