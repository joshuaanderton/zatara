<?php

namespace Zatara\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Zatara extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'zatara';
    }
}
