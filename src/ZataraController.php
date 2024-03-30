<?php

namespace Zatara;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class ZataraController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function __call($method, $parameters)
    {
        // $action = ZataraFacade::getAction($method);

        // if ($action) {
        //     return $action->handle(request());
        // }

        // return parent::__call($method, $parameters);
    }
}
