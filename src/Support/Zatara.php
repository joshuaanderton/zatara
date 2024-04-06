<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\PendingSingletonResourceRegistration;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use SplFileInfo;
use Zatara\Enums\CRUD;
use Zatara\Http\Controllers\ZataraResourceController;
use Zatara\Http\Controllers\ZataraSingletonController;
use Zatara\RouteAction;

class Zatara
{
    protected Application $app;

    public Collection $actions;

    public function __construct()
    {
        //
    }

    public function namespace(?string ...$namespaces): string
    {
        $namespace = str('App\\Http\\RouteActions')->explode('\\');
        $appends = collect($namespaces)->map(fn ($n) => rtrim($n, '.php'));

        return $namespace->concat($appends)->join('\\');
    }

    public function getActions(): Collection
    {
        if ((new ReflectionProperty($this, 'actions'))->isInitialized($this)) {
            return $this->actions;
        }

        $namespace = str($this->namespace())->remove('App\\');
        $zataraDir = app_path($namespace->replace('\\', '/'));

        return $this->actions = collect(File::allFiles($zataraDir))
            ->map(fn (mixed $file) => str_replace('/', '\\', rtrim($file->getRelativePathname(), '.php')))
            ->map(fn (string $classname) => (new ReflectionZatara($classname))->toArray());
    }

    public function routes(): void
    {
        $actions = $this->getActions();

        // Group by resource name
        $actions
            ->mapToGroups(fn ($action) => [$action['resource'] => $action]) // Group into potential resources
            ->map(function ($resourceActions, $resource) {

                $basename = str($resource)->afterLast('.');
                $isSingleton = ! $basename->is($basename->plural()->toString());

                $defaultActions = $resourceActions
                    ->filter(fn ($action) => CRUD::in($action['name']))
                    ->filter(fn ($action) => ! $isSingleton || ($isSingleton && ! CRUD::in($action['name'], CRUD::INDEX)))
                    ->pluck('name');

                // if ($resource == 'dashboard.products') {
                //     dd($defaultActions);
                // }

                // Register custom actions
                $resourceActions
                    ->filter(fn ($action) => ! $defaultActions->contains($action['name']))
                    ->each(fn ($action) =>
                        Route::match(['get'], $action['uri'], $action['action']['uses'])
                            ->name($action['action']['as'])
                            ->middleware($action['action']['middleware'])
                    );

                if ($defaultActions->isEmpty()) {
                    return null;
                }

                $data = [
                    'singleton' => $isSingleton,
                    'name' => str($resource)->replace('.', '/')->toString(),
                    'controller' => RouteAction::class,
                ];

                $options = collect();

                $missingActions = collect(CRUD::cases())
                    ->pluck('value')
                    ->filter(fn ($ea) => ! $isSingleton || $ea !== CRUD::INDEX->value)
                    ->filter(fn ($ea) => ! $defaultActions->contains($ea));

                if ($missingActions->count() > 0) {
                    $options = $options->put('except', $missingActions->toArray());
                }

                if (! $isSingleton) {
                    if ($options->isNotEmpty()) {
                        $data['options'] = $options->toArray();
                    }

                    return $data;
                }

                if ($defaultActions->contains(CRUD::CREATE->value)) {
                    $options = $options->put('creatable', true);
                }
                if ($defaultActions->contains(CRUD::DESTROY->value)) {
                    $options = $options->put('destroyable', true);
                }

                if ($options->isNotEmpty()) {
                    $data['options'] = $options->toArray();
                }

                return $data;

            })
            ->whereNotNull()
            ->map(fn (array $resource) => // Register resources/default route actions after custom route actions
                $resource['singleton']
                    ? Route::singleton($resource['name'], $resource['controller'], $resource['options'])
                    : Route::resource($resource['name'], $resource['controller'], $resource['options'])
            );

        // Define explicit model bindings
        $actions
            ->pluck('params')
            ->collapse()
            ->unique()
            ->each(fn ($model, $param) => (
                Route::bind($param, fn ($value) =>
                    $model::where((new $model)->getRouteKeyName(), $value)
                        ->firstOrFail()
                )
            ));
    }
}
