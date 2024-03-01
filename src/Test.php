<?php

namespace Zatara;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

abstract class Test
{
    abstract public function handle(Request $request): JsonResponse;
}
