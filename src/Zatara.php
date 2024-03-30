<?php

declare(strict_types=1);

namespace Zatara;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Zatara\Support\ZataraObject;
use Zatara\Traits\WithHelpers;

abstract class Zatara
{
    use WithHelpers;

    /**
     * Handle the incoming request. Return array of data for the current client state or a redirect response to a new state.
     */
    abstract public function handle(Request $request): array|\Illuminate\Http\RedirectResponse;

    protected Request $request;

    protected ?User $user;

    protected ?Team $team;

    protected ZataraObject $action;

    public function __invoke(Request $request): mixed
    {
        $this->action = ZataraObject::fromRequest($request);

        return $this
            ->setRequest($request)
            ->authorizeCondition($request)
            ->validateRules($request)
            ->getResponse();
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
}
