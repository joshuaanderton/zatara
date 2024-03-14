<?php

namespace Zatara\Actions;

use Illuminate\Http\Request as HttpRequest;

class ClientConnect
{
    public function __invoke(HttpRequest $request)
    {
        $classname = str($request->action)->explode('.')->map(fn ($str) => str($str)->studly()->toString());
        $classname = str($classname->join('\\'))->prepend('Zatara\\Actions\\')->toString();

        return $classname::run($request);
    }
}
