<?php

namespace Esoboy\Ui\Support;

/**
 * Links a trigger to the popover it opens.
 *
 * Blade renders a trigger before its content, so the trigger opens an id and the
 * content closes it; anything rendered in between (menu items) reads the current one.
 *
 * Ids must survive Livewire re-renders, or morphing swaps the element and closes it:
 * they count up per Livewire component (or per page outside one), restarting on
 * every render, so the same render yields the same ids.
 */
final class Overlay
{
    /** @var list<array{string, string}> [prefix, id] */
    private array $open = [];

    /** @var array<string, int> */
    private array $counts = [];

    public static function open(string $prefix): string
    {
        $overlay = app(self::class);
        $id = $overlay->next($prefix);

        $overlay->open[] = [$prefix, $id];

        return $id;
    }

    /**
     * The innermost open id with this prefix.
     */
    public static function current(string $prefix): string
    {
        $overlay = app(self::class);

        foreach (array_reverse($overlay->open) as [$openPrefix, $id]) {
            if ($openPrefix === $prefix) {
                return $id;
            }
        }

        return $overlay->next($prefix);
    }

    public static function close(string $prefix): string
    {
        $overlay = app(self::class);

        return array_pop($overlay->open)[1] ?? $overlay->next($prefix);
    }

    /**
     * Restart a Livewire component's count when it renders.
     */
    public function restart(string $scope): void
    {
        unset($this->counts[$scope]);
    }

    private function next(string $prefix): string
    {
        $scope = Model::component()?->getId() ?? 'page';

        $this->counts[$scope] = ($this->counts[$scope] ?? 0) + 1;

        return "{$prefix}-{$scope}-{$this->counts[$scope]}";
    }
}
