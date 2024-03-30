<?php

namespace Zatara\Enums;

use Zatara\Enums\Traits\WithIn;

enum CRUD: string
{
    use WithIn;

    case INDEX = 'index';
    case CREATE = 'create';
    case STORE = 'store';
    case SHOW = 'show';
    case EDIT = 'edit';
    case UPDATE = 'update';
    case DESTROY = 'destroy';
}
