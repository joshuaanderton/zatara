<?php

namespace Zatara\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Response as InertiaResponse;
use Lorisleiva\Actions\Action;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Zatara\Http\Traits\AsZataraAction;

abstract class ZataraShow
{
    use AsZataraAction;

    abstract public function handle(Request $request, Model $model): array;
}
