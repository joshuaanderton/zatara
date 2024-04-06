<?php

namespace Zatara;

use Illuminate\Http\RedirectResponse;

class RouteAction extends \Zatara\Zatara
{
    public function handle(\Illuminate\Http\Request $request): array|RedirectResponse
    {
        return [];
    }
}
