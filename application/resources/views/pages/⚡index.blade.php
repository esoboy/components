<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="mx-auto max-w-4xl space-y-10 p-10">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold">esoboy/ui</h1>
        <p class="text-muted-foreground">shadcn/ui for Laravel 13 and Livewire 4. Each page shows a group of components working against a Livewire component.</p>
    </div>

    @foreach (config('demo') as $group => $pages)
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">{{ $group }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($pages as $path => [$label, $components])
            <a href="/{{ $path }}" class="block">
                <x-ui::card class="h-full transition-colors hover:bg-accent/50">
                    <x-ui::card.header>
                        <x-ui::card.title>{{ $label }}</x-ui::card.title>
                        <x-ui::card.description>{{ $components }}</x-ui::card.description>
                    </x-ui::card.header>
                </x-ui::card>
            </a>
            @endforeach
        </div>
    </section>
    @endforeach
</div>
