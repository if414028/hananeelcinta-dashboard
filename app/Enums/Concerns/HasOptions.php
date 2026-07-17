<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait HasOptions
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case): array => [$case->value => $case->label()],
        )->all();
    }
}
