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
     * @return array|\Illuminate\Http\RedirectResponse
     */
    abstract public function handle(Request $request): array|\Illuminate\Http\RedirectResponse;

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
        $classname = get_called_class();

        $paramsKeys = (
            str($classname::getUri())
                ->explode('{')
                ->filter(fn ($str) => str($str)->contains('}'))
                ->map(fn ($str) => str($str)->before('}'))
        );

        if ($paramsKeys->count() > 0) {
            $model = $this->lookupModel($request);

            $request->merge([
                $this->action->route->param => $model
            ]);
        }

        $this->lookupParams($request);

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
        if (class_uses($classname, HasInertia::class) && $this->acceptsInertia($request)) {
            return $this->inertiaResponse($request, $response);
        }

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        return response()->json([], 200);
    }

    protected function lookupParams(Request $request)
    {
        $model = null;

        $modelParam = $this->action->route->param;
        $model = $this->action->model;
        $model = $model::firstWhere((new $model)->getRouteKeyName(), $request->$modelParam ?? null);

        if ($model === null) {
            throw new ModelNotFoundException;
        }

        return $model;
    }

    protected function acceptsInertia(Request $request): bool
    {
        return class_exists(\Inertia\Inertia::class) && ! ($request->ajax() && ! $request->headers->get('X-Inertia'));
    }

    public function getUri(string $classname): string
    {
        if ($uri = (new ReflectionProperty($classname, 'uri'))->getDefaultValue() ?? null) {
            return $uri;
        }

        $uri = (
            str($classname)
                ->remove(Zatara::routeActionNamespace())
                ->explode('\\')
                ->map(fn ($str) => str($str)->snake('-')->toString())
                ->join('/')
        );

        $action = class_basename(get_called_class());

        if (Zatara::getMethodForActionName($action) !== null) {
            $uri = str($uri)->beforeLast("/{$action}");
        }

        $modelKey = null;
        $parseClassname = str(get_called_class())->remove(Zatara::routeActionNamespace())->explode('\\');
        $crudLookupActions = ['show', 'update', 'edit', 'destroy'];

        if (in_array($action, $crudLookupActions)) {
            $modelKey = (
                str(
                    $parseClassname
                        ->reverse()
                        ->slice(1, 1)
                        ->first()
                )
                    ->singular()
                    ->snake()
                    ->toString()
            );

            if (! str($uri)->contains('/'.'{'.$modelKey.'}')) {
                $uri = str($uri)->append('/'.'{'.$modelKey.'}')->toString();
            }
        }

        return $uri;
    }

    public static function getRoute(): string
    {
        $parseClassname = str(get_called_class())->remove(Zatara::routeActionNamespace())->explode('\\');
        return (
            $parseClassname
                ->map(fn ($str) => str($str)->snake('-')->toString())
                ->join('.')
        );
    }

    public static function getMiddleware(): array
    {
        $middleware = collect('web');

        $middleware = $middleware->merge(
            static::getProp('middlewareAppend', [])
        );

        if (str(get_called_class())->startsWith(Zatara::routeActionNamespace().'Dashboard')) {
            $middleware->merge([
                'auth:sanctum',
                'verified',
                'Laravel\Jetstream\Http\Middleware\AuthenticateSession'
            ]);
        }

        return $middleware->toArray();
    }

    public static function getMethod(): string
    {
        $action = str(class_basename(get_called_class()))->lower()->toString();

        $method = (
            static::getProp('method', Zatara::getMethodForActionName($action), 'get')
        );

        return $method;
    }

    public static function getView()
    {
        $parseClassname = str(get_called_class())->remove(Zatara::routeActionNamespace())->explode('\\');
        return $parseClassname->map(fn ($str) => str($str)->studly()->toString())->join('/');
    }

    public static function getProp(string $property, mixed ...$default): array|string|null
    {
        $classname = get_called_class();
        $value = (new ReflectionProperty($classname, $property))->getDefaultValue() ?? null;

        if ($value === null) {
            return collect($default)->filter(fn ($def) => ! ! $def)->first() ?? null;
        }

        return $value;
    }
}
