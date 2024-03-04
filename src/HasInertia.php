<?php

namespace Zatara;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait HasInertia
{
    protected function inertiaResponse(Request $request, array|RedirectResponse $response)
    {
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $view = static::getView();

        // Check for Jetstream support
        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render($request, $view, $response);
        }

        return \Inertia\Inertia::render($view, $response);
    }
}
