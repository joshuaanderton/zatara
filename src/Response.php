<?php

namespace Zatara;

use Illuminate\Http\JsonResponse;

class Response extends JsonResponse
{
    public function __construct(array $data, array $headers = [])
    {
        parent::__construct(
            data: $data,
            status: 200,
            headers: $headers,
            options: 0
        );
    }
}
