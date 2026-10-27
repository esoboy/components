<?php

namespace Esoboy\Ui\Support;

use DateTimeInterface;

/**
 * Date Picker presets as calendar values: label => "Y-m-d" (single), a list of
 * them (multiple) or ['from' => …, 'to' => …] (range, also [from, to]).
 * Dates may be strings or DateTimeInterface.
 */
final class DatePresets
{
    public static function normalize(array $presets, string $mode): array
    {
        return array_map(fn ($value) => match ($mode) {
            'range' => [
                'from' => self::key($value['from'] ?? $value[0] ?? null),
                'to' => self::key($value['to'] ?? $value[1] ?? null),
            ],
            'multiple' => array_values(array_map(self::key(...), (array) $value)),
            default => self::key($value),
        }, $presets);
    }

    private static function key(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) return $date->format('Y-m-d');

        return $date === null || $date === '' ? null : substr((string) $date, 0, 10);
    }
}
