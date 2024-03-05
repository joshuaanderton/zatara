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

    public string $classname;

    protected string $routeActionNamespace;

    protected Collection $parseClassname;

    public string $uri;

    public string $method;

    public array $middleware;

    public string $as;

    public string $view;

    public function __construct(string $classname)
    {
        $this->classname = $classname;
        $this->routeActionNamespace = Zatara::routeActionNamespace();
        $this->parseClassname = str($this->classname)->remove($this->routeActionNamespace)->explode('\\');
        $this->uri = $this->getUri();
        $this->method = $this->getMethod();
        $this->middleware = $this->getMiddleware();
        $this->as = $this->getAs();
        $this->view = $this->getView();
    }

    private function getRouteAction(): string
    {
        return str(class_basename($this->classname))->lower()->toString();
    }

    private function getUri(): string
    {
        if ($uri = $this->getProp('uri') ?? null) {
            return $uri;
        }

        $uri = $this->parseClassname->map(fn ($str) => str($str)->snake('-')->toString())->join('/');
        $action = $this->getRouteAction();

        if ($this->getMethodForActionName() !== null) {
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

        $middleware = $middleware->merge(
            $this->getProp('middlewareAppend', [])
        );

        if (
            str($this->classname)
                ->startsWith($this->routeActionNamespace.'Dashboard')
        ) {
            $middleware = $middleware->merge([
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession'
            ]);
        }

        return $middleware->toArray();
    }

    private function getMethod(): string
    {
        return $this->getProp('method', $this->getMethodForActionName(), 'get');
    }

    private function getView()
    {
        return $this->parseClassname->map(fn ($str) => str($str)->studly()->toString())->join('/');
    }

    private function getProp(string $property, mixed ...$default): array|string|null
    {
        $value = (new ReflectionProperty($this->classname, $property))->getDefaultValue() ?? null;

        if ($value === null) {
            return collect($default)->filter(fn ($def) => ! ! $def)->first() ?? null;
        }

        return $value;
    }

    public function getMethodForActionName()
    {
        $name = $this->getRouteAction();

        return self::CRUD_ACTIONS[$name] ?? null;
    }
}
