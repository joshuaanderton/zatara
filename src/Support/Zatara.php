<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Zatara
{
    protected Application $app;

    public Collection $actions;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->actions = $this->buildActions();
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

    public function buildAction(string $classname): object
    {
        $parseClassname = str($classname)->remove('App\\Http\\Zatara\\')->explode('\\');

        $crudActions = [
            'index' => 'get',
            'show' => 'get',
            'edit' => 'get',
            'create' => 'get',
            'store' => 'post',
            'update' => 'put',
            'destroy' => 'delete'
        ];

        $action = str(
            $parseClassname->last()
        )->lower()->toString();

        $classInstance = new $classname;

        if (! $uri = $classInstance->uri ?? false) {
            $uri = (
                $parseClassname
                    ->map(fn ($str) => str($str)->snake('-')->toString())
                    ->join('/')
            );

            if ($classInstance->prefix ?? false) {
                $uri = str($uri)->explode('/')->reverse()->values();
                $uri[1] = $classInstance->prefix;
                $uri = $uri->reverse()->join('/');
            }
        }

        if (in_array($action, array_keys($crudActions))) {
            $uri = str($uri)->explode('/')->slice(0, -1)->join('/');
        }

        $data = [
            'classname' => $classname,
            'view' => (
                $parseClassname
                    ->map(fn ($str) => str($str)->camel('-')->ucfirst()->toString())
                    ->join('/')
            ),
            'route' => (object) [
                'uri' => $uri,
                'method' => $crudActions[$action] ?? 'post',
            ],
            'action' => $action,
            'name' => (
                str($parseClassname->join('.'))
                    ->lower()
                    ->toString()
            ),
            'model_key' => $modelKey = (
                str(
                    $parseClassname
                        ->reverse()
                        ->slice(1, 1)
                        ->first()
                )
                    ->singular()
                    ->snake()
                    ->toString()
            ),
            'model_classname' => str($modelKey)->studly()->prepend("\\App\\Models\\")->toString(),
        ];

        return (object) $data;
    }

    public function buildActions(): Collection
    {
        $classFiles = File::allFiles(app_path('Http/Zatara'));

        return collect($classFiles)->map(fn ($file) => $this->buildAction(
            'App\\Http\\Zatara\\' . (
                str($file->getRelativePathname())
                    ->remove('.php')
                    ->explode('/')
                    ->map(fn ($str) => str($str)->studly()->toString())
                    ->join('\\')
            )
        ));
    }
}
