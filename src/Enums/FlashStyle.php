<?php

namespace Zatara\Enums;

use Zatara\Enums\Traits\WithIn;

enum FlashStyle: string
{
    use WithIn;

    case SUCCESS = 'success';
    case DANGER = 'danger';
    case INFORMATION = 'information';
}
