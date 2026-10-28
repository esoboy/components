<?php

namespace Esoboy\Ui\Livewire;

use Attribute;
use Esoboy\Ui\Support\RichText;
use Livewire\Attribute as LivewireAttribute;

/**
 * #[Sanitize] on a property an Editor binds to: the HTML the browser sends is cleaned
 * as soon as it is set, so updated hooks and actions find it clean on the property (a
 * hook's argument is still what was sent: Livewire passes it on as it came). List it
 * before #[Validate] for live validation to check the clean HTML.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sanitize extends LivewireAttribute
{
    public function update(string $fullPath, mixed $newValue): callable
    {
        return function () use ($fullPath) {
            $value = data_get($this->component, $fullPath);

            if (is_string($value)) {
                data_set($this->component, $fullPath, RichText::sanitize($value));
            }
        };
    }
}
