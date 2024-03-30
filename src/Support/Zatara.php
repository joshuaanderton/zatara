<?php

namespace Zatara\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionProperty;
use Zatara\Support\ZataraObject;

class Zatara
{
    protected Application $app;

    public Collection $actions;

    public function __construct()
    {
        //
    }

    public function namespace(?string ...$namespaces): string
    {
        $namespace = str('App\\Zatara')->explode('\\');
        $appends = collect($namespaces)->map(fn ($n) => rtrim($n, '.php'));

        return $namespace->concat($appends)->join('\\');
    }

    public function getActions(): Collection
    {
        if ((new ReflectionProperty($this, 'actions'))->isInitialized($this)) {
            return $this->actions;
        }

        $namespace = str($this->namespace())->remove('App\\');
        $zataraDir = app_path($namespace->replace('\\', '/'));

        return $this->actions = collect(File::allFiles($zataraDir))->map(fn ($file) =>
            (new ZataraObject(
                str_replace('/', '\\', rtrim($file->getRelativePathname(), '.php'))
            ))->toArray()
        );
    }
}
