<?php

namespace Zatara\Support;

use Illuminate\Support\Collection;
use ReflectionProperty;
use Zatara\Support\Zatara;

class ActionMeta
{
    const CRUD_ACTIONS = [
        'index' => 'get',
        'show' => 'get',
        'edit' => 'get',
        'create' => 'get',
        'store' => 'post',
        'update' => 'put',
        'destroy' => 'delete'
    ];

    public string $actionClassname;

    protected string $actionNamespace;

    protected Collection $parseClassname;

    public string $uri;

    public string $method;

    public array $middleware;

    public string $as;

    public string $view;

    public function __construct(string $actionClassname)
    {
        $this->actionClassname = $actionClassname;
        $this->actionNamespace = Zatara::actionNamespace();
        $this->parseClassname = str($this->actionClassname)->remove($this->actionNamespace)->explode('\\');
        $this->uri = $this->getUri();
        $this->method = $this->getMethod();
        $this->middleware = $this->getMiddleware();
        $this->as = $this->getAs();
        $this->view = $this->getView();
    }

    private function getRouteAction(): string
    {
        return str(class_basename($this->actionClassname))->lower()->toString();
    }

    private function getUri(): string
    {
        $prop = new ReflectionProperty($this->actionClassname, 'uri');

        if ($uri = $prop->getDefaultValue() ?? null) {
            return $uri;
        }

        if ($this->actionClassname === 'App\\Zatara\\Welcome') {
            return '/';
        }

        $uri = $this->parseClassname->map(fn ($str) => str($str)->snake('-')->toString())->join('/');
        $action = $this->getRouteAction();

        if (in_array($action, ['index', 'store', 'destroy', 'update'])) {
            $uri = str($uri)->beforeLast("/{$action}");
        }

        $modelKey = null;
        $crudLookupActions = ['show', 'update', 'edit', 'destroy'];

        if (in_array($action, $crudLookupActions)) {
            $modelKey = (
                str(
                    $this->parseClassname
                        ->reverse()
                        ->slice(1, 1)
                        ->first()
                )
                    ->singular()
                    ->snake()
                    ->toString()
            );

            if (! str($uri)->contains('/'.'{'.$modelKey.'}')) {
                $uri = str($uri)->append('/'.'{'.$modelKey.'}')->toString();
            }
        }

        return $uri;
    }

    private function getAs(): string
    {
        return (
            $this->parseClassname
                ->map(fn ($str) => str($str)->snake('-')->toString())
                ->join('.')
        );
    }

    private function getMiddleware(): array
    {
        $middleware = collect('web');

        if (
            str($this->actionClassname)->startsWith($this->actionNamespace.'Dashboard')
        ) {
            $middleware = $middleware->merge([
                'auth',
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession'
            ]);
        }

        return $middleware->toArray();
    }

    private function getView()
    {
        return $this->parseClassname->map(fn ($str) => str($str)->studly()->toString())->join('/');
    }

    private function getMethod(): string
    {
        $method = $this->getMethodForActionName();

        if ($method === null) {
            $name = str($this->actionClassname);

            if ($name->endsWith('Store')) {
                $method = 'post';
            } else if ($name->endsWith('Update')) {
                $method = 'put';
            } else if ($name->endsWith(['Destroy', 'Remove'])) {
                $method = 'delete';
            }
        }

        return $method ?: 'get';
    }

    public function getMethodForActionName()
    {
        $name = $this->getRouteAction();

        return self::CRUD_ACTIONS[$name] ?? null;
    }
}
