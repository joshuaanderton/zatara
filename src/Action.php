<?php

namespace Zatara;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

abstract class Action
{
    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     */
    abstract public function handle(Request $request): array;

    protected Request $request;
    protected ?User $user;
    protected ?Team $team;

    final public function __invoke(HttpRequest $request): mixed
    {
        return (
            $this
                ->setRequest($request)
                ->authorizeCondition()
                ->validateRules()
                ->getResponse()
        );
    }

    public function response(Request $request, Response $response): mixed
    {
        $middleware = 'App\\Http\\Middleware\\HandleZataraRequests';
        $middleware = class_exists($middleware) ? $middleware : Middleware::class;

        return (new $middleware)->response($request, $response);
    }

    public function headers(Request $request): array
    {
        return [];
    }

    final private function getResponse(): mixed
    {
        return $this->response(
            request: $this->request,
            response: new Response(
                data: $this->handle($this->request),
                headers: $this->headers($this->request)
            )
        );
    }

    public function condition(Request $request): bool
    {
        return true;
    }

    final private function setRequest(HttpRequest $request): self
    {
        $this->request = Request::createFrom($request);
        $this->user = $this->request->user();
        $this->team = $this->request->user()?->currentTeam;

        return $this;
    }

    final private function authorizeCondition(): self
    {
        Gate::allowIf(
            condition: $this->condition($this->request)
        );

        return $this;
    }

    public function rules(Request $request): array
    {
        return [];
    }

    final private function validateRules(): self
    {
        $validator = Validator::make(
            data: $this->request->all(),
            rules: $this->rules($this->request)
        );

        $validator->validate();

        return $this;
    }
}
