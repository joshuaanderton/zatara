<?php

namespace Zatara;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Zatara\Support\ActionMeta;

trait HasInertia
{
    public function acceptsInertia(Request $request): bool
    {
        return class_exists(\Inertia\Inertia::class) && ! ($request->ajax() && ! $request->headers->get('X-Inertia'));
    }

    public function inertiaResponse(Request $request, array|RedirectResponse $response)
    {
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $view = (new ActionMeta(get_called_class()))->view;

        // Check for Jetstream support
        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render($request, $view, $response);
        }

        return \Inertia\Inertia::render($view, $response);
    }
}
