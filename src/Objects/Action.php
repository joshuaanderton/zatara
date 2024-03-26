<?php

namespace Zatara\Objects;

use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Zatara\Support\Zatara;

class Action
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
        $this->parseClassname = str($this->actionClassname)->remove(Zatara::actionNamespace())->explode('\\');
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
        $uri = $this->parseClassname
            ->map(fn ($str) => str($str)->snake('-')->toString())
            ->join('/');

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
            $this->actionClassname === Zatara::actionNamespace().'Welcome' ||
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
            str($this->actionClassname)->startsWith(Zatara::actionNamespace('Dashboard'))
        ) {
            $middleware = $middleware->merge([
                'auth',
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession',
            ]);
        }

        // if (
        //     str($this->actionClassname)->startsWith(Zatara::actionNamespace('Api'))
        // ) {
        //     $middleware = $middleware->merge([
        //         'api',
        //         'auth:sanctum',
        //     ]);
        // }

        return $middleware->toArray();
    }

    private function getView()
    {
        return $this->parseClassname->map(fn ($str) => str($str)->studly()->toString())->join('/');
    }

    private function getMethods(): array
    {
        $method = match ($this->getName()->toString()) {
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
