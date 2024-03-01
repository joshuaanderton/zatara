<?php

namespace Zatara\Http\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Response as InertiaResponse;
use Zatara\Support\Facades\Zatara;

trait AsZataraAction
{
    public ?string $prefix = null;

    public ?string $uri = null;

    protected ?object $action = null;

    public function __invoke(Request $request): Response|RedirectResponse|InertiaResponse
    {
        $this->action = Zatara::actionFromRequest($request);

        if (in_array($this->action->action, ['show', 'update', 'edit', 'destroy'])) {
            $model = $this->lookupModel($request);
            $data = $this->handle($request, $model);

            return $this->render($data);
        }

        $data = $this->handle($request);

        return $this->render($data);
    }

    protected function lookupModel(Request $request)
    {
        $modelKey = $this->action->model_key;
        $modelClassname = $this->action->model_classname;
        $model = $modelClassname::firstWhere((new $modelClassname)->getRouteKeyName(), $request->$modelKey ?? null);

        if ($model === null) {
            throw new ModelNotFoundException;
        }

        return $model;
    }

    protected function render(array $data)
    {
        if (class_exists(\Inertia\Inertia::class)) {
            if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
                return \Laravel\Jetstream\Jetstream::inertia()->render(request(), $this->action->view, $data);
            }

            return \Inertia\Inertia::render($this->action->view, $data);
        }

        return $data;
    }
}
