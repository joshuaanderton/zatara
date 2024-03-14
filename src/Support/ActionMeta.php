<?php

namespace Zatara\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

class ActionMeta
{
    public string $actionClassname;

    protected Collection $parseClassname;

    public string $uri;

    public array $methods;

    public array $middleware;

    public string $as;

    public string $view;

    public function __construct(string $actionClassname)
    {
        $this->actionClassname = $actionClassname;
        $this->parseClassname = str($this->actionClassname)->remove(Zatara::getNamespace())->explode('\\');
        $this->uri = $this->getUri();
        $this->methods = $this->getMethods();
        $this->middleware = $this->getMiddleware();
        $this->as = $this->getAs();
        $this->view = $this->getView();
    }

    private function getName(): Stringable
    {
        return str(class_basename($this->actionClassname))->snake('-');
    }

    private function getUri(): string
    {
        $uri = $this->parseClassname->map(fn ($str) => str($str)->snake('-')->toString())->join('/');
        $action = $this->getName()->toString();
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

            $uri = str($uri)->replaceLast("/{$action}", "/{{$modelKey}}/{$action}")->toString();
        }

        if (
            $this->actionClassname === 'App\\Zatara\\Welcome' ||
            in_array($action, ['index', 'store', 'destroy', 'update'])
        ) {
            $uri = str($uri)->beforeLast("{$action}")->rtrim('/');
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
            str($this->actionClassname)->startsWith(Zatara::getNamespace('Dashboard'))
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

    private function getMethods(): array
    {
        $method = match($this->getName()->toString()) {
            'index' => 'get',
            'show' => 'get',
            'edit' => 'get',
            'create' => 'get',
            'store' => 'post',
            'update' => 'put',
            'destroy' => 'delete',
            default => 'get',
        };

        return [$method];
    }
}
