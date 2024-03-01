<?php

namespace Zatara\Http;

use Illuminate\Http\Request;
use Zatara\Http\Traits\AsZataraAction;

abstract class ZataraIndex
{
    use AsZataraAction;

    abstract public function handle(Request $request): array;
}
