<?php

namespace Zatara\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
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
        // Define explicit model bindings
        collect(File::files(app_path('Models')))
            ->map(function ($file) {
                $name = str($file->getRelativePathname())->remove('.php');
                return [
                    $name->prepend("App\\Models\\")->toString() => $name->snake()->toString()
                ];
            })
            ->collapse()
            ->filter(fn ($param) => (
                ZataraFacade::actions()
                    ->pluck('uri')
                    ->filter(fn ($str) => str($str)->contains("{{$param}}"))
                    ->count() > 0
            ))
            ->each(fn ($param, $model) => Route::model($param, $model));


        Route::middleware('web')->group(function () {
            ZataraFacade::actions()->each(fn (array $action) => (
                Route::match(
                    $action['methods'],
                    $action['uri'],
                    $action['action']['uses']
                )
                    ->name($action['action']['as'])
                    ->middleware($action['action']['middleware'])
            ));
        });
    }
}
