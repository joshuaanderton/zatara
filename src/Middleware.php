<?php

namespace Zatara;

abstract class Middleware
{
    public function response(Request $request, Response $response): mixed
    {
        return $response;
    }
}
