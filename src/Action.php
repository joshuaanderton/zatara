<?php

namespace Zatara;

use Illuminate\Http\Request as HttpRequest;
use Zatara\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

abstract class Action
{
    public string $uri;

    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     * @param \Zatara\Request $request
     * @return array
     */
    abstract public function handle(Request $request): array;

    final public function __invoke(HttpRequest $request): mixed
    {
        $request = Request::createFrom($request);

        // Authorize request
        Gate::allowIf($this->authorize($request));

        // Validate request data
        Validator::make($request->all(), $this->validate($request))->validate();

        // Pass request to action handler
        $data = $this->handle($request);

        // Create response from action data
        $response = new Response($data, 200);

        return $this->response($request, $response);
    }

    public function response(Request $request, Response $response): mixed
    {
        $middleware = 'App\\Http\\Middleware\\HandleZataraRequests';
        $middleware = class_exists($middleware) ? $middleware : Middleware::class;

        return (new $middleware)->response($request, $response);
    }

    public function authorize(Request $request): bool
    {
        return true;
    }

    public function validate(Request $request): array
    {
        return [];
    }
}
