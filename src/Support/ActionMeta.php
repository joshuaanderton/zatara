<?php

namespace Zatara\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use ReflectionProperty;

class ActionMeta
{
    public string $actionClassname;

    protected Collection $parseClassname;

    public string $uri;

    public string $method;

    public array $middleware;

    public string $as;

    public string $view;

    public function __construct(string $actionClassname)
    {
        $this->actionClassname = $actionClassname;
        $this->parseClassname = str($this->actionClassname)->remove(Zatara::actionNamespace())->explode('\\');
        $this->uri = $this->getUri();
        $this->method = $this->getMethod();
        $this->middleware = $this->getMiddleware();
        $this->as = $this->getAs();
        $this->view = $this->getView();
    }

    private function actionName(): Stringable
    {
        return str(class_basename($this->actionClassname))->snake('-');
    }

    private function getUri(): string
    {
        $prop = new ReflectionProperty($this->actionClassname, 'uri');

        if ($uri = $prop->getDefaultValue() ?? null) {
            return $uri;
        }

        $uri = $this->parseClassname->map(fn ($str) => str($str)->snake('-')->toString())->join('/');
        $action = $this->actionName()->toString();

        if (
            $this->actionClassname === 'App\\Zatara\\Welcome' ||
            in_array($action, ['index', 'store', 'destroy', 'update'])
        ) {
            $uri = str($uri)->beforeLast("{$action}")->rtrim('/');
        }

        $modelKey = null;

        if (in_array($action, ['show', 'update', 'edit', 'destroy'])) {
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

            $uri = str($uri)->append("/{{$modelKey}}")->toString();
        }

        return $uri;
    }

    private function getAs(): string
    {
        return
            $this->parseClassname
                ->map(fn ($str) => str($str)->snake('-')->toString())
                ->join('.');
    }

    private function getMiddleware(): array
    {
        $middleware = collect('web');

        if (
            str($this->actionClassname)->startsWith(Zatara::actionNamespace('Dashboard'))
        ) {
            $middleware = $middleware->merge([
                'auth',
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession',
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
        $name = $this->actionName();

        return match($name->toString()) {
            'index' => 'get',
            'show' => 'get',
            'edit' => 'get',
            'create' => 'get',
            'store' => 'post',
            'update' => 'put',
            'destroy' => 'delete',
            default => 'get',
        };
    }
}
