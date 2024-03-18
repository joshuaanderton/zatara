<?php

namespace Zatara;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Response as InertiaResponse;

abstract class Action
{
    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     */
    abstract public function handle(Request $request): array|RedirectResponse|InertiaResponse;

    protected Request $request;
    protected ?User $user;
    protected ?Team $team;

    public function __invoke(Request $request): mixed
    {
        return (
            $this
                ->setRequest($request)
                ->authorizeCondition()
                ->validateRules()
                ->getResponse()
        );
    }

    private function getResponse(): mixed
    {
        $request = $this->request;
        $responseData = $this->handle($request);

        if ($request->wantsJson()) {
            return response()->json($responseData);
        }

        if (
            $responseData instanceof RedirectResponse ||
            $responseData instanceof InertiaResponse ||
            ! class_exists(\Inertia\Inertia::class)
        ) {
            return $responseData;
        }

        $page = $this->inertiaView($request);

        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render(request(), $page, $responseData);
        }

        return \Inertia\Inertia::render($page, $responseData);
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

    public function condition(Request $request): bool
    {
        return true;
    }

    private function setRequest(Request $request): self
    {
        $this->request = Request::createFrom($request);
        $this->user = $this->request->user();
        $this->team = $this->request->user()?->currentTeam;

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
