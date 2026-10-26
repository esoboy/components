<?php

namespace Esoboy\Ui\Support;

use Illuminate\View\ComponentAttributeBag;

/**
 * The current value behind a control's wire:model, read from the Livewire component
 * being rendered, so a control can paint its initial state on the server.
 */
final class Model
{
    public static function value(ComponentAttributeBag $attributes, mixed $default = null): mixed
    {
        $component = app('view')->shared('__livewire');
        $key = self::key($attributes);

        if ($component === null || $key === null) {
            return $default;
        }

        return data_get($component, $key, $default);
    }

    private static function key(ComponentAttributeBag $attributes): ?string
    {
        foreach ($attributes->getAttributes() as $attribute => $value) {
            if (str_starts_with($attribute, 'wire:model') && is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
