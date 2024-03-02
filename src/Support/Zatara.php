<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionProperty;

class Zatara
{
    protected Application $app;

    public Collection $actions;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->actions = $this->buildActions();
    }

    public function getActionNamespace(): string
    {
        return 'App\\Http\\Zatara\\';
    }

    public function actions()
    {
        return $this->actions;
    }

    public function actionFromRequest(Request $request): object
    {
        $classname = str($request->route()->getAction('uses'))->explode('@')->first();

        return $this->actions->where('classname', $classname)->first();
    }

    public function actionUri(string $classname): string
    {
        if ($uri = (new ReflectionProperty($classname, 'uri'))->getDefaultValue() ?? null) {
            return $uri;
        }

        return (
            str($classname)
                ->remove($this->getActionNamespace())
                ->explode('\\')
                ->map(fn ($str) => str($str)->snake('-')->toString())
                ->join('/')
        );
    }

    public function actionMiddleware(string $classname): array
    {
        $middleware = collect('web');

        $middleware = $middleware->merge(
            (new ReflectionProperty($classname, 'middlewareAppend'))->getDefaultValue() ?? []
        );

        if (str($classname)->startsWith($this->getActionNamespace().'Dashboard')) {
            $middleware->merge([
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession'
            ]);
        }

        return $middleware->toArray();
    }

    public function buildAction(string $classname): object
    {
        $parseClassname = str($classname)->remove($this->getActionNamespace())->explode('\\');
        $middleware = Zatara::actionMiddleware($classname);
        $uri = Zatara::actionUri($classname);

        $crudActions = [
            'index' => 'get',
            'show' => 'get',
            'edit' => 'get',
            'create' => 'get',
            'store' => 'post',
            'update' => 'put',
            'destroy' => 'delete'
        ];

        $crudLookupActions = [
            'show',
            'update',
            'edit',
            'destroy'
        ];

        $action = $parseClassname->map('strtolower')->last();

        if (in_array($action, array_keys($crudActions))) {
            $uri = str($uri)->beforeLast("/{$action}");
        }

        $modelKey = null;

        if (in_array($action, $crudLookupActions)) {
            $modelKey = (
                str(
                    $parseClassname
                        ->reverse()
                        ->slice(1, 1)
                        ->first()
                )
                    ->singular()
                    ->snake()
                    ->toString()
            );
        }

        $data = [
            'classname' => $classname,
            'view' => $parseClassname->map(fn ($str) => str($str)->studly()->toString())->join('/'),
            'model' => str($modelKey)->studly()->prepend("\\App\\Models\\")->toString(),
            'route' => (object) [
                'uri' => $uri,
                'name' => $parseClassname->map(fn ($str) => str($str)->snake('-')->toString())->join('.'),
                'method' => $crudActions[$action] ?? 'post',
                'middleware' => $middleware,
                'param' => $modelKey,
            ]
        ];

        return (object) $data;
    }

    public function buildActions(): Collection
    {
        $classFiles = File::allFiles(base_path('app/Http/Zatara'));

        return collect($classFiles)->map(fn ($file) => $this->buildAction(
            $this->getActionNamespace() . (
                str($file->getRelativePathname())
                    ->remove('.php')
                    ->explode('/')
                    ->map(fn ($str) => str($str)->studly()->toString())
                    ->join('\\')
            )
        ));
    }
}
