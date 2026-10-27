<?php

use Livewire\Component;

new class extends Component
{
    public int $progress = 60;

    public bool $details = false;

    public function advance(): void
    {
        $this->progress = min(100, $this->progress + 10);
    }
};
?>

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Accordion</h2>
        <x-ui::accordion default-value="shipping" class="max-w-lg">
            <x-ui::accordion.item value="shipping">
                <x-ui::accordion.trigger>What are your shipping options?</x-ui::accordion.trigger>
                <x-ui::accordion.content>We offer standard (5-7 days), express (2-3 days), and overnight shipping. Free shipping on international orders.</x-ui::accordion.content>
            </x-ui::accordion.item>
            <x-ui::accordion.item value="returns">
                <x-ui::accordion.trigger>What is your return policy?</x-ui::accordion.trigger>
                <x-ui::accordion.content>Returns accepted within 30 days. Items must be unused and in original packaging. Refunds processed within 5-7 business days.</x-ui::accordion.content>
            </x-ui::accordion.item>
            <x-ui::accordion.item value="support" disabled>
                <x-ui::accordion.trigger>How can I contact customer support?</x-ui::accordion.trigger>
                <x-ui::accordion.content>Reach us via email, live chat, or phone.</x-ui::accordion.content>
            </x-ui::accordion.item>
        </x-ui::accordion>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Collapsible</h2>
        <x-ui::collapsible wire:model.live="details" class="flex w-[350px] flex-col gap-2">
            <div class="flex items-center justify-between gap-4 px-4">
                <h4 class="text-sm font-semibold">Order #4189 ({{ $details ? 'open' : 'closed' }})</h4>
                <x-ui::button variant="ghost" size="icon" class="size-8" data-collapsible-trigger>
                    <x-ui::icon name="chevrons-up-down" />
                    <span class="sr-only">Toggle details</span>
                </x-ui::button>
            </div>
            <div class="flex items-center justify-between rounded-md border px-4 py-2 text-sm"><span class="text-muted-foreground">Status</span><span class="font-medium">Shipped</span></div>
            <x-ui::collapsible.content class="flex flex-col gap-2">
                <div class="rounded-md border px-4 py-2 text-sm">Shipping address: 100 Market St, San Francisco</div>
                <div class="rounded-md border px-4 py-2 text-sm">Items: 2x Studio Headphones</div>
            </x-ui::collapsible.content>
        </x-ui::collapsible>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Progress</h2>
        <x-ui::progress :value="$progress" class="w-[60%]" />
        <x-ui::progress :value="$progress" class="w-full max-w-sm">
            <x-ui::progress.label>Upload progress</x-ui::progress.label>
            <x-ui::progress.value />
        </x-ui::progress>
        <x-ui::button variant="outline" size="sm" wire:click="advance">Advance</x-ui::button>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Aspect Ratio</h2>
        <x-ui::aspect-ratio :ratio="16 / 9" class="w-full max-w-sm rounded-lg bg-muted">
            <div class="flex size-full items-center justify-center text-sm text-muted-foreground">16 / 9</div>
        </x-ui::aspect-ratio>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Scroll Area</h2>
        <div class="flex gap-6">
            <x-ui::scroll-area class="h-72 w-48 rounded-md border">
                <div class="p-4">
                    <h4 class="mb-4 text-sm leading-none font-medium">Tags</h4>
                    @foreach (range(50, 1) as $n)
                    <div class="text-sm">v1.2.0-beta.{{ $n }}</div>
                    <x-ui::separator class="my-2" />
                    @endforeach
                </div>
            </x-ui::scroll-area>
            <x-ui::scroll-area class="w-96 rounded-md border whitespace-nowrap">
                <div class="flex w-max space-x-4 p-4">
                    @foreach (['Ornella Binni', 'Tom Byrom', 'Vladimir Malyavko', 'Ana Silva'] as $artist)
                    <figure class="shrink-0">
                        <div class="h-40 w-36 rounded-md bg-muted"></div>
                        <figcaption class="pt-2 text-xs text-muted-foreground">Photo by <span class="font-semibold text-foreground">{{ $artist }}</span></figcaption>
                    </figure>
                    @endforeach
                </div>
            </x-ui::scroll-area>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Resizable</h2>
        <x-ui::resizable.panel-group class="max-w-md rounded-lg border">
            <x-ui::resizable.panel default-size="50">
                <div class="flex h-[200px] items-center justify-center p-6"><span class="font-semibold">One</span></div>
            </x-ui::resizable.panel>
            <x-ui::resizable.handle with-handle />
            <x-ui::resizable.panel default-size="50">
                <x-ui::resizable.panel-group orientation="vertical">
                    <x-ui::resizable.panel default-size="25" min-size="15">
                        <div class="flex h-full items-center justify-center p-6"><span class="font-semibold">Two</span></div>
                    </x-ui::resizable.panel>
                    <x-ui::resizable.handle />
                    <x-ui::resizable.panel>
                        <div class="flex h-full items-center justify-center p-6"><span class="font-semibold">Three</span></div>
                    </x-ui::resizable.panel>
                </x-ui::resizable.panel-group>
            </x-ui::resizable.panel>
        </x-ui::resizable.panel-group>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Carousel</h2>
        <div class="px-14">
            <x-ui::carousel class="w-full max-w-xs">
                <x-ui::carousel.content>
                    @foreach (range(1, 5) as $n)
                    <x-ui::carousel.item>
                        <x-ui::card><x-ui::card.content class="flex aspect-square items-center justify-center p-6"><span class="text-4xl font-semibold">{{ $n }}</span></x-ui::card.content></x-ui::card>
                    </x-ui::carousel.item>
                    @endforeach
                </x-ui::carousel.content>
                <x-ui::carousel.previous />
                <x-ui::carousel.next />
            </x-ui::carousel>
        </div>
        <div class="px-14">
            <x-ui::carousel align="start" class="w-full max-w-sm">
                <x-ui::carousel.content>
                    @foreach (range(1, 5) as $n)
                    <x-ui::carousel.item class="basis-1/3">
                        <x-ui::card><x-ui::card.content class="flex aspect-square items-center justify-center p-6"><span class="text-2xl font-semibold">{{ $n }}</span></x-ui::card.content></x-ui::card>
                    </x-ui::carousel.item>
                    @endforeach
                </x-ui::carousel.content>
                <x-ui::carousel.previous />
                <x-ui::carousel.next />
            </x-ui::carousel>
        </div>
    </section>
</div>
