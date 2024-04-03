<?php

namespace Zatara\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Zatara\Enums\CRUD;

class ZataraObject
{
    public string $classname;

    protected Collection $classnameShards;

    public string $uri;

    public array $methods;

    public array $middleware;

    public array $params;

    public string $as;

    public string $inertiaComponent;

    public string $controller;

    public function __construct(string $classname)
    {
        $this->classname = $classname;
        $this->classnameShards = str($this->classname)->explode('\\');
        $this->uri = $this->getUri();
        $this->methods = $this->getMethods();
        $this->middleware = $this->getMiddleware();
        $this->as = $this->getAs();
        $this->params = $this->getParams();
        $this->inertiaComponent = $this->getInertiaComponent();
        $this->controller = $this->getController();
    }

    public static function fromRequest(Request $request): self
    {
        return new static(
            classname: str($request->route()->getAction('controller'))->before('@')
        );
    }

    private function getName(): Stringable
    {
        return str($this->classnameShards->last())->snake('-');
    }

    private function getParams(): array
    {
        $modelNamespace = str('App\\Models\\');

        return $this->classnameShards
            ->map(fn (string $str) => str($str)->snake('_')->singular()->toString())
            ->filter(fn (string $str) => class_exists($modelNamespace->append(str($str)->studly())->toString()))
            ->map(fn (string $str) => [$str => $modelNamespace->append(str($str)->studly())->toString()])
            ->collapse()
            ->toArray();
    }

    private function getUri(): string
    {
        $uri = $this->classnameShards
            ->map(fn ($str) => str($str)->snake('-'))
            ->join('/');

        $action = $this->getName()->toString();

        // Add '{param}' keys into URI for each model parameter (should use laravel tool for this later)
        // dashboard/shops/products/edit => dashboard/shops/{shop}/products/{product}/edit
        $params = $this->getParams();
        $lastParamVal = end($params);

        // Replace /products/edit with /products/{product}/edit (for example)
        foreach ($params as $paramKey => $paramVal) {

            // TODO: Should account for singleton routes (e.g. /account/edit)
            // $modelSlug === str($paramKey)->plural()->toString())

            // Don't add params to index, store, create, or non-crud actions
            if ($paramVal === $lastParamVal && (
                CRUD::in($action, CRUD::INDEX, CRUD::STORE, CRUD::CREATE)
            )) {
                continue;
            }

            $uri = str($uri)->replaceFirst(
                str($paramKey)->plural()->toString(),
                str($paramKey)->plural()->append("/{{$paramKey}}")->toString()
            )->toString();
        }

        // Remove the action's name from certain route URI's (e.g. welcome page, store actions, etc.)
        if (
            $this->classname === 'Welcome' ||
            (CRUD::in($action) && ! CRUD::in($action, CRUD::EDIT, CRUD::CREATE))
        ) {
            $uri = str($uri)->beforeLast("{$action}")->rtrim('/');
        }

        return $uri;
    }

    private function getAs(): string
    {
        return
            $this->classnameShards
                ->map(fn ($str) => str($str)->snake('-'))
                ->join('.');
    }

    private function getMiddleware(): array
    {
        $middleware = collect('web');

        if (
            str($this->classname)->startsWith('Dashboard\\')
        ) {
            $middleware = $middleware->merge([
                'auth',
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession',
            ]);
        }

        if (
            str($this->classname)->startsWith('Api\\')
        ) {
            $middleware = $middleware->merge([
                'api',
                'auth:sanctum',
            ]);
        }

        return $middleware->toArray();
    }

    private function getInertiaComponent()
    {
        return str($this->classname)
            ->after((new Zatara)->namespace(''))
            ->replace('\\', '/')
            ->toString();
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

    public function getController()
    {
        return (new Zatara)->namespace($this->classname);
    }

    public function toArray()
    {
        return [
            'uri' => $this->uri,
            'inertia_page' => $this->inertiaComponent,
            'methods' => $this->methods,
            'params' => $this->params,
            'action' => [
                'uses' => [$this->controller, '__invoke'],
                'controller' => $this->controller,
                'middleware' => $this->middleware,
                'as' => $this->as,
            ],
        ];
    }
}
