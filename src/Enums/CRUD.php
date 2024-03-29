<?php

namespace Zatara\Enums;

enum CRUD: string
{
    case INDEX = 'index';
    case CREATE = 'create';
    case STORE = 'store';
    case SHOW = 'show';
    case EDIT = 'edit';
    case UPDATE = 'update';
    case DESTROY = 'destroy';

    public static function in(string $value, ?self ...$cases): bool
    {
        if (empty($cases)) {
            $cases = self::cases();
        }

        return collect($cases)
            ->map(fn ($case) => $case->value)
            ->where(null, '===', $value)
            ->isNotEmpty();
    }
}
