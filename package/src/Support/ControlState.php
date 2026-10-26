<?php

namespace Esoboy\Ui\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

/**
 * Validation state of a form control, derived from what it is bound to.
 */
final class ControlState
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        public readonly ?string $key,
        public readonly array $messages,
        public readonly ?string $errorId,
        public readonly bool $showError,
    ) {}

    public static function for(ComponentAttributeBag $attributes, ViewErrorBag|MessageBag|null $errors, bool $showError = true): self
    {
        $key = self::key($attributes);
        $messages = self::messages($errors, $key);

        $errorId = $messages
            ? ($attributes->get('id') ?? 'ui-'.str_replace('.', '-', $key)).'-error'
            : null;

        return new self($key, $messages, $errorId, $showError);
    }

    /**
     * The validation key a control is bound to: its wire:model target, or its name in dot notation.
     */
    public static function key(ComponentAttributeBag $attributes): ?string
    {
        foreach ($attributes->getAttributes() as $attribute => $value) {
            if (str_starts_with($attribute, 'wire:model') && is_string($value) && $value !== '') {
                return $value;
            }
        }

        $name = $attributes->get('name');

        if (! is_string($name) || $name === '') {
            return null;
        }

        // items[0][qty] -> items.0.qty, tags[] -> tags
        return trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    }

    /**
     * Unique messages for a key, falling back to its nested keys (key.*).
     *
     * @return list<string>
     */
    public static function messages(ViewErrorBag|MessageBag|null $errors, ?string $key, string $bag = 'default'): array
    {
        if ($errors === null || $key === null || $key === '') {
            return [];
        }

        $messages = $errors instanceof ViewErrorBag ? $errors->getBag($bag) : $errors;

        return array_values(array_unique(
            $messages->get($key) ?: Arr::flatten($messages->get($key.'.*'))
        ));
    }

    public function invalid(): bool
    {
        return $this->messages !== [];
    }

    public function showsError(): bool
    {
        return $this->showError && $this->invalid();
    }

    /**
     * ARIA attributes for the control itself.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_filter([
            'aria-invalid' => $this->invalid() ? 'true' : null,
            'aria-describedby' => $this->showsError() ? $this->errorId : null,
        ]);
    }
}
