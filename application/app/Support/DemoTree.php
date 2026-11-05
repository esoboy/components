<?php

namespace App\Support;

class DemoTree
{
    /** @return list<array<string, mixed>> */
    public function __invoke(string $value): array
    {
        $teams = ['eng' => ['Web', 'Mobile'], 'design' => ['Brand', 'Product'], 'sales' => ['EMEA', 'APAC']];

        return str_contains($value, '.')
            ? array_map(fn ($name) => ['value' => "{$value}.".strtolower($name), 'label' => $name], ['Ada', 'Grace', 'Linus'])
            : array_map(fn ($team) => ['value' => "{$value}.".strtolower($team), 'label' => $team, 'lazy' => true], $teams[$value] ?? []);
    }
}
