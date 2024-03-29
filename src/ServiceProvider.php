<?php

namespace Zatara;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Zatara\Support\Facades\Zatara as ZataraFacade;
use Zatara\Support\Zatara;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->singleton('zatara', fn (Application $app) => new Zatara($app));
    }

    public function boot()
    {
        $actions = ZataraFacade::getActions();

        $actions->each(fn (array $action) => (
            Route::match(
                $action['methods'],
                $action['uri'],
                $action['action']['uses']
            )
                ->name($action['action']['as'])
                ->middleware($action['action']['middleware'])
        ));

        // Define explicit model bindings
        $actions
            ->pluck('params')
            ->collapse()
            ->unique()
            ->each(fn ($model, $param) => (
                Route::bind($param, fn ($value) =>
                    $model::where((new $model)->getRouteKeyName(), $value)->firstOrFail()
                )
            ));
    }
}
