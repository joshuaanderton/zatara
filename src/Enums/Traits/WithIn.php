<?php

namespace Zatara\Enums\Traits;

trait WithIn
{
    public static function in(string|self $value, ?self ...$cases): bool
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
