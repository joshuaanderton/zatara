<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Zatara\Objects\Action;

class Zatara
{
    protected Application $app;

    public Collection $actions;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->actions = $this->buildActions();
    }

    public static function actionNamespace(?string ...$namespace): string
    {
        return
            collect(['App', 'Zatara'])
                ->concat(collect($namespace))
                ->push('')
                ->join('\\');
    }

    public function getActions()
    {
        return $this->actions;
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

        return collect($classFiles)->map(function ($file) use ($actionNamespace) {
            $classname = (
                $actionNamespace
                    ->append(
                        str($file->getRelativePathname())
                            ->remove('.php')
                            ->explode('/')
                            ->map(fn ($str) => str($str)->studly()->toString())
                            ->join('\\')
                    )
                    ->toString()
            );

            $actionMeta = new Action($classname);

            return [
                'uri' => $actionMeta->uri,
                'methods' => $actionMeta->methods,
                'action' => [
                    'uses' => [$classname, '__invoke'],
                    'controller' => $classname,
                    'middleware' => $actionMeta->middleware,
                    'as' => $actionMeta->as,
                ],
            ];

        });
    }
}
