<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
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

    public static function actionNamespace(): string
    {
        return 'App\\Zatara\\';
    }

    public function actions()
    {
        return $this->actions;
    }

    /**
     * @param string $classname
     * @return array
     */
    public function buildAction(string $classname): array
    {
        $actionMeta = new ActionMeta($classname);

        return [
            'uri' => $actionMeta->uri,
            'methods' => [
                $actionMeta->method
            ],
            'action' => [
                'uses' => [$classname, '__invoke'],
                'controller' => $classname,
                'middleware' => $actionMeta->middleware,
                'as' => $actionMeta->as,
                // 'namespace' => null
                // 'prefix' => '',
                // 'where' => []
            ]
        ];
    }

    public function buildActions(): Collection
    {
        $actionNamespace = str($this->actionNamespace());
        $classFiles = File::allFiles(
            base_path(
                $actionNamespace
                    ->replace('\\', '/')
                    ->replace('App', 'app')
            )
        );

        return collect($classFiles)->map(fn ($file) => $this->buildAction(
            $actionNamespace
                ->append(
                    str($file->getRelativePathname())
                        ->remove('.php')
                        ->explode('/')
                        ->map(fn ($str) => str($str)->studly()->toString())
                        ->join('\\')
                )
                ->toString()
        ));
    }
}
