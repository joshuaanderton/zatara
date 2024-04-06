<?php

declare(strict_types=1);

namespace Zatara;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Response as InertiaResponse;
use Zatara\Enums\CRUD;
use Zatara\Enums\FlashStyle;
use Zatara\Support\Facades\Zatara as ZataraFacade;

abstract class Zatara
{
    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     */
    abstract public function handle(Request $request): array|RedirectResponse;

    protected Request $request;

    protected ?User $user;

    protected ?Team $team;

    public function __invoke(Request $request): JsonResponse|InertiaResponse|RedirectResponse
    {
        return $this
            ->setRequest($request)
            ->authorizeCondition($request)
            ->validateRules($request)
            ->getResponse();
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (CRUD::in($name)) {
            $request = request();

            $actionClassname = str($request->route()->getAction()['as'])
                ->prepend('app.http.route-actions.')
                ->explode('.')
                ->map(fn ($str) => str($str)->studly())
                ->join('\\');

            $request->merge($arguments);

            return (new $actionClassname)->__invoke($request);
        }

        return $this->$name(...$arguments);
    }

    private function setRequest(Request $request): self
    {
        $this->request = Request::createFrom($request);
        $this->user = $this->request->user();
        $this->team = $this->request->user()?->currentTeam;

        return $this;
    }

    private function getResponse(): RedirectResponse|InertiaResponse|JsonResponse
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

        return $this->render(null, $responseData);
    }

    public function render(?string $component, array $data): InertiaResponse
    {
        if (! $component) {
            // Automatically look for the component based on Inertia namespace
            $routeName = $this->request->route()->getAction()['as'];
            $component = str($routeName)->explode('.')->map(fn ($str) => str($str)->studly())->join('/');
        }

        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render($this->request, $component, $data);
        }

        return \Inertia\Inertia::render($component, $data);
    }

    public function flash(string $message, ?FlashStyle $style = FlashStyle::SUCCESS): void
    {
        if (! FlashStyle::in($style)) {
            $style = FlashStyle::INFORMATION;
        }

        session()->flash('flash.banner', $message);
        session()->flash('flash.bannerStyle', $style->value);
    }

    public function condition(Request $request): bool
    {
        return true;
    }

    protected function authorizeCondition(): self
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

    protected function validateRules(): self
    {
        $validator = Validator::make(
            data: $this->request->all(),
            rules: $this->rules($this->request)
        );

        $validator->validate();

        return $this;
    }
}
