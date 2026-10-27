<?php

namespace Esoboy\Ui\Support;

/**
 * Cascader options: a tree of ['value' => ..., 'label' => ..., 'children' => [...],
 * 'disabled' => bool]. Values compare as strings, as they do in the browser.
 */
final class CascaderOptions
{
    /**
     * The labels along a value path, or [] when the path isn't in the tree.
     *
     * @param  list<array<string, mixed>>  $options
     * @return list<string>
     */
    public static function labels(array $options, mixed $path): array
    {
        $labels = [];

        foreach (array_values((array) $path) as $value) {
            $option = collect($options)->first(fn ($option) => (string) ($option['value'] ?? '') === (string) $value);

            if ($option === null) {
                return [];
            }

            $labels[] = (string) ($option['label'] ?? $option['value']);
            $options = $option['children'] ?? [];
        }

        return $labels;
    }

    /**
     * The tree with only the keys the browser needs.
     *
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    public static function normalize(array $options): array
    {
        return array_values(array_map(fn (array $option) => array_filter([
            'value' => (string) $option['value'],
            'label' => (string) ($option['label'] ?? $option['value']),
            'disabled' => ! empty($option['disabled']) ?: null,
            'children' => ! empty($option['children']) ? self::normalize($option['children']) : null,
        ], fn ($value) => $value !== null), $options));
    }
}
