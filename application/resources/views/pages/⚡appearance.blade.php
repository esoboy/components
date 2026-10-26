<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?string $lastChange = null;

    #[On('ui-appearance-changed')]
    public function appearanceChanged(string $value, string $resolved): void
    {
        $this->lastChange = "{$value} ({$resolved})";
    }

    public function forceDark(): void
    {
        $this->dispatch('ui-set-appearance', value: 'dark');
    }
};
?>

<div class="mx-auto max-w-3xl space-y-10 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Appearance (Alpine magic)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button variant="outline" x-on:click="$appearance.set('light')">Light</x-ui::button>
            <x-ui::button variant="outline" x-on:click="$appearance.set('dark')">Dark</x-ui::button>
            <x-ui::button variant="outline" x-on:click="$appearance.set('system')">System</x-ui::button>
            <x-ui::button x-on:click="$appearance.toggle()">Toggle</x-ui::button>
        </div>
        <p class="text-muted-foreground" x-data>
            value: <span id="appearance-value" x-text="$appearance.value"></span>,
            resolved: <span id="appearance-resolved" x-text="$appearance.resolved"></span>
        </p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Livewire</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button variant="secondary" wire:click="forceDark">Dark from server</x-ui::button>
            <span class="text-muted-foreground">last change: <span id="last-change">{{ $lastChange ?? '—' }}</span></span>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">dark: variant</h2>
        <div id="variant-box" class="rounded-lg bg-amber-100 p-4 text-amber-900 dark:bg-indigo-950 dark:text-indigo-100">
            amber in light, indigo in dark
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Scoped island</h2>
        <div id="island" data-appearance="dark" class="space-y-3 rounded-lg bg-background p-4 text-foreground">
            <p class="dark:text-indigo-300">Always dark, whatever the page uses.</p>
            <x-ui::button>Primary</x-ui::button>
            <x-ui::button variant="outline">Outline</x-ui::button>
        </div>
    </section>
</div>
