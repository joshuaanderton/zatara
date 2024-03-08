<?php

namespace Zatara;

abstract class Middleware
{
    public function response(Request $request, Response $response): mixed
    {
        return $response;
    }

    protected function inertiaView(Request $request): string
    {
        return (
            str($request->route()->getAction('controller'))
                ->before('@')
                ->remove('App\\Zatara\\')
                ->replace('\\', '/')
        );
    }
}
