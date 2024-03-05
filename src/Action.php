<?php

namespace Zatara;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;
use Zatara\Support\Facades\Zatara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use ReflectionProperty;
use Zatara\Support\ActionMeta;

abstract class Action
{
    use HasInertia;

    public string $uri;

    /**
     * @var "get"|"post"|"put"|"delete"
     */
    public string $method;

    /**
     * Array of middleware keys or classnames to append to the default middleware.
     * @var string[]
     */
    public array $middlewareAppend = [];

    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     * @param Request $request
     * @return array
     */
    abstract public function handle(Request $request): array;

    public function gate(Request $request): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        return [];
    }

    protected function to(string $name, ?array $parameters = []): RedirectResponse
    {
        return redirect()->route($name, $parameters);
    }

    protected function back(): RedirectResponse
    {
        return redirect()->back();
    }

    public function __invoke(Request $request): JsonResponse|RedirectResponse|InertiaResponse
    {
        $currentRoute = $request->route();
        $classname = str($currentRoute->getAction('controller'))->before('@')->toString();

        if (method_exists($classname, 'gate')) {
            Gate::allowIf(
                $this->gate($request)
            );
        }

        if (method_exists($classname, 'rules')) {
            $request->validate(
                $this->rules($request)
            );
        }

        $response = $this->handle($request);

        // InertiaJS Support
        // class_uses($classname, HasInertia::class) &&
        if ($this->acceptsInertia($request)) {
            return $this->inertiaResponse($request, $response);
        }

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        return response()->json($response, 200);
    }
}
