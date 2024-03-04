<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionProperty;

class Zatara
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

    protected Application $app;

    public Collection $actions;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->actions = $this->buildActions();
    }

    public function routeActionNamespace(): string
    {
        return 'App\\Zatara\\Http\\';
    }

    public function actions()
    {
        return $this->actions;
    }

    /**
     * @param string $classname
     * @return object
     */
    public function buildAction(string $classname): object
    {
        return (object) [
            'classname' => $classname,
            'route' => $classname::getRoute(),
            'uri' => $classname::getUri(),
            'method' => $classname::getMethod(),
            'middleware' => $classname::getMiddleware(),
        ];
    }

    public function buildActions(): Collection
    {
        $classFiles = File::allFiles(
            base_path(
                str($this->routeActionNamespace())
                    ->replace('\\', '/')
                    ->replace('App', 'app')
            )
        );

        return collect($classFiles)->map(fn ($file) => $this->buildAction(
            $this->routeActionNamespace() . (
                str($file->getRelativePathname())
                    ->remove('.php')
                    ->explode('/')
                    ->map(fn ($str) => str($str)->studly()->toString())
                    ->join('\\')
            )
        ));
    }

    /**
     * @param $name "index"|"show"|"edit"|"create"|"store"|"update"|"destroy"
     */
    public function getMethodForActionName(string $name)
    {
        return self::CRUD_ACTIONS[$name] ?? null;
    }
}
