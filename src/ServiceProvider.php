<?php

namespace Zatara;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Zatara\Actions\ClientConnect;
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
            ->pluck('uri')
            ->map(fn ($str): Collection => str($str)->matchAll('/\{([a-z_]+)\}/'))
            ->flatten()
            ->unique()
            ->map(fn ($param) => ['\\App\\Models\\'.str($param)->studly()->toString() => $param])
            ->collapse()
            ->filter(fn ($param, $model) => class_exists($model))
            ->each(fn ($param, $model) => (
                Route::bind($param, fn (string $value) => (
                    str(request()->route()->getAction('controller'))->startsWith(Zatara::getNamespace())
                        ? $model::where((new $model)->getRouteKeyName(), $value)->firstOrFail()
                        : $value
                ))
            ));

        Route::middleware(['web'])
            ->match(['get', 'post', 'delete'], 'zatara/{action}', ClientConnect::class)
            ->name('zatara.connection');
    }
}
