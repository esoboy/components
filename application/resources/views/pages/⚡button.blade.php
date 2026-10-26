<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        usleep(600_000);

        $this->count++;
    }
};
?>

<div class="mx-auto max-w-3xl space-y-10 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Variants</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button>Default</x-ui::button>
            <x-ui::button variant="secondary">Secondary</x-ui::button>
            <x-ui::button variant="outline">Outline</x-ui::button>
            <x-ui::button variant="ghost">Ghost</x-ui::button>
            <x-ui::button variant="destructive">Destructive</x-ui::button>
            <x-ui::button variant="link">Link</x-ui::button>
            <x-ui::button href="#" variant="outline">Anchor</x-ui::button>
            <x-ui::button disabled>Disabled</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Sizes</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button size="xs">Extra small</x-ui::button>
            <x-ui::button size="sm">Small</x-ui::button>
            <x-ui::button>Default</x-ui::button>
            <x-ui::button size="lg">Large</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Utility overrides</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button class="bg-rose-600 hover:bg-rose-700 rounded-full px-8">bg-rose-600 rounded-full px-8</x-ui::button>
            <x-ui::button variant="outline" class="h-12 border-dashed text-base">h-12 border-dashed</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Livewire loading</h2>
        <div class="flex items-center gap-3">
            <x-ui::button wire:click="increment">Increment</x-ui::button>
            <span class="text-muted-foreground">{{ $count }}</span>
        </div>
    </section>
</div>
