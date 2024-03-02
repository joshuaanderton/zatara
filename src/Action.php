<?php

namespace Zatara;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;
use Zatara\Support\Facades\Zatara;
use Illuminate\Http\Request;

abstract class Action
{
    public string $uri;

    /**
     * @var get|post|put|delete
     */
    public string $method;

    /**
     * @var string[]
     */
    public array $middlewareAppend = [];

    protected ?object $action = null;

    abstract public function handle(Request $request): array|\Illuminate\Http\RedirectResponse;

    public function __invoke(Request $request): JsonResponse|RedirectResponse|InertiaResponse
    {
        $this->action = Zatara::actionFromRequest($request);

        if ($this->action->route->param) {
            $request->merge([
                $this->action->route->param => $this->lookupModel($request)
            ]);
        }

        $data = $this->handle($request);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        // InertiaJS Support
        if ($this->isInertia($request)) {
            if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
                return \Laravel\Jetstream\Jetstream::inertia()->render(request(), $this->action->view, $data);
            }

            return \Inertia\Inertia::render($this->action->view, $data);
        }

        return response()->json($data, 200);
    }

    protected function lookupModel(Request $request)
    {
        $modelParam = $this->action->route->param;
        $model = $this->action->model;
        $model = $model::firstWhere((new $model)->getRouteKeyName(), $request->$modelParam ?? null);

        if ($model === null) {
            throw new ModelNotFoundException;
        }

        return $model;
    }

    protected function isInertia(Request $request): bool
    {
        return class_exists(\Inertia\Inertia::class) && ! ($request->ajax() && ! $request->headers->get('X-Inertia'));
    }
}
