<?php

namespace Zatara;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

abstract class Inertia
{
    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     */
    abstract public function handle(Request $request): array|RedirectResponse;

    protected Request $request;

    public function __invoke(HttpRequest $request): mixed
    {
        return (
            $this
                ->setRequest($request)
                ->authorizeCondition()
                ->validateRules()
                ->getResponse()
        );
    }

    public function response(Response $response): InertiaResponse
    {
        $data = json_decode($response->getContent(), true);
        $page = $this->inertiaView();

        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render(request(), $page, $data);
        }

        return \Inertia\Inertia::render($page, $data);
    }

    protected function inertiaView(): string
    {
        return (
            str($this->request->route()->getAction('controller'))
                ->before('@')
                ->remove('App\\Zatara\\')
                ->replace('\\', '/')
        );
    }

    private function getResponse(): InertiaResponse|RedirectResponse
    {
        $data = $this->handle($this->request);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        $page = $this->inertiaView();

        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render(request(), $page, $data);
        }

        return \Inertia\Inertia::render($page, $data);
    }

    public function condition(Request $request): bool
    {
        return true;
    }

    private function setRequest(HttpRequest $request): self
    {
        $this->request = Request::createFrom($request);

        return $this;
    }

    private function authorizeCondition(): self
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

    private function validateRules(): self
    {
        $validator = Validator::make(
            data: $this->request->all(),
            rules: $this->rules($this->request)
        );

        $validator->validate();

        return $this;
    }
}
