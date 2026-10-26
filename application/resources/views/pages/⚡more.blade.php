<?php

use Livewire\Component;

new class extends Component
{
    public int $progress = 60;

    public bool $details = false;

    public bool $bookmarks = true;

    public string $person = 'pedro';

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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
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
        <h2 class="text-lg font-semibold">Hover Card</h2>
        <x-ui::hover-card>
            <x-ui::hover-card.trigger variant="link" href="#" :delay="10" :close-delay="100">Hover Here</x-ui::hover-card.trigger>
            <x-ui::hover-card.content class="flex w-64 flex-col gap-0.5">
                <div class="font-semibold">@nextjs</div>
                <div>The React Framework – created and maintained by @vercel.</div>
                <div class="mt-1 text-xs text-muted-foreground">Joined December 2021</div>
            </x-ui::hover-card.content>
        </x-ui::hover-card>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Context Menu</h2>
        <x-ui::context-menu>
            <x-ui::context-menu.trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">Right click here</x-ui::context-menu.trigger>
            <x-ui::context-menu.content class="w-48">
                <x-ui::context-menu.group>
                    <x-ui::context-menu.item>Back <x-ui::context-menu.shortcut>⌘[</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                    <x-ui::context-menu.item disabled>Forward <x-ui::context-menu.shortcut>⌘]</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                    <x-ui::context-menu.item>Reload <x-ui::context-menu.shortcut>⌘R</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                    <x-ui::context-menu.sub>
                        <x-ui::context-menu.sub-trigger>More Tools</x-ui::context-menu.sub-trigger>
                        <x-ui::context-menu.sub-content>
                            <x-ui::context-menu.item>Save Page...</x-ui::context-menu.item>
                            <x-ui::context-menu.item>Create Shortcut...</x-ui::context-menu.item>
                            <x-ui::context-menu.separator />
                            <x-ui::context-menu.item variant="destructive">Delete</x-ui::context-menu.item>
                        </x-ui::context-menu.sub-content>
                    </x-ui::context-menu.sub>
                </x-ui::context-menu.group>
                <x-ui::context-menu.separator />
                <x-ui::context-menu.checkbox-item wire:model.live="bookmarks">Show Bookmarks</x-ui::context-menu.checkbox-item>
                <x-ui::context-menu.separator />
                <x-ui::context-menu.radio-group wire:model.live="person">
                    <x-ui::context-menu.label inset>People</x-ui::context-menu.label>
                    <x-ui::context-menu.radio-item value="pedro">Pedro Duarte</x-ui::context-menu.radio-item>
                    <x-ui::context-menu.radio-item value="colm">Colm Tuite</x-ui::context-menu.radio-item>
                </x-ui::context-menu.radio-group>
            </x-ui::context-menu.content>
        </x-ui::context-menu>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Menubar</h2>
        <x-ui::menubar class="w-fit">
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>File</x-ui::menubar.trigger>
                <x-ui::menubar.content>
                    <x-ui::menubar.item>New Tab <x-ui::menubar.shortcut>⌘T</x-ui::menubar.shortcut></x-ui::menubar.item>
                    <x-ui::menubar.item>New Window <x-ui::menubar.shortcut>⌘N</x-ui::menubar.shortcut></x-ui::menubar.item>
                    <x-ui::menubar.item disabled>New Incognito Window</x-ui::menubar.item>
                    <x-ui::menubar.separator />
                    <x-ui::menubar.sub>
                        <x-ui::menubar.sub-trigger>Share</x-ui::menubar.sub-trigger>
                        <x-ui::menubar.sub-content>
                            <x-ui::menubar.item>Email link</x-ui::menubar.item>
                            <x-ui::menubar.item>Messages</x-ui::menubar.item>
                        </x-ui::menubar.sub-content>
                    </x-ui::menubar.sub>
                    <x-ui::menubar.separator />
                    <x-ui::menubar.item>Print... <x-ui::menubar.shortcut>⌘P</x-ui::menubar.shortcut></x-ui::menubar.item>
                </x-ui::menubar.content>
            </x-ui::menubar.menu>
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>View</x-ui::menubar.trigger>
                <x-ui::menubar.content class="w-64">
                    <x-ui::menubar.checkbox-item wire:model.live="bookmarks">Always Show Bookmarks Bar</x-ui::menubar.checkbox-item>
                    <x-ui::menubar.separator />
                    <x-ui::menubar.item inset>Reload <x-ui::menubar.shortcut>⌘R</x-ui::menubar.shortcut></x-ui::menubar.item>
                </x-ui::menubar.content>
            </x-ui::menubar.menu>
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>Profiles</x-ui::menubar.trigger>
                <x-ui::menubar.content>
                    <x-ui::menubar.radio-group wire:model.live="person">
                        <x-ui::menubar.radio-item value="pedro">Pedro</x-ui::menubar.radio-item>
                        <x-ui::menubar.radio-item value="colm">Colm</x-ui::menubar.radio-item>
                    </x-ui::menubar.radio-group>
                </x-ui::menubar.content>
            </x-ui::menubar.menu>
        </x-ui::menubar>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Navigation Menu</h2>
        <x-ui::navigation-menu>
            <x-ui::navigation-menu.list>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.trigger>Getting started</x-ui::navigation-menu.trigger>
                    <x-ui::navigation-menu.content>
                        <ul class="grid w-96 gap-1 p-1">
                            @foreach (['Introduction' => 'Re-usable components built with Tailwind CSS.', 'Installation' => 'How to install dependencies and structure your app.', 'Typography' => 'Styles for headings, paragraphs, lists...etc'] as $title => $text)
                            <li>
                                <x-ui::navigation-menu.link href="#" class="flex-col items-start gap-1">
                                    <div class="text-sm leading-none font-medium">{{ $title }}</div>
                                    <p class="line-clamp-2 text-sm leading-snug text-muted-foreground">{{ $text }}</p>
                                </x-ui::navigation-menu.link>
                            </li>
                            @endforeach
                        </ul>
                    </x-ui::navigation-menu.content>
                </x-ui::navigation-menu.item>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.trigger>Components</x-ui::navigation-menu.trigger>
                    <x-ui::navigation-menu.content>
                        <ul class="grid w-[500px] grid-cols-2 gap-1 p-1">
                            @foreach (['Alert Dialog', 'Hover Card', 'Progress', 'Scroll-area', 'Tabs', 'Tooltip'] as $title)
                            <li><x-ui::navigation-menu.link href="#">{{ $title }}</x-ui::navigation-menu.link></li>
                            @endforeach
                        </ul>
                    </x-ui::navigation-menu.content>
                </x-ui::navigation-menu.item>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.link href="#">Docs</x-ui::navigation-menu.link>
                </x-ui::navigation-menu.item>
            </x-ui::navigation-menu.list>
        </x-ui::navigation-menu>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Drawer</h2>
        <div class="flex gap-2">
            <x-ui::drawer name="goal" show-swipe-handle>
                <x-ui::drawer.trigger variant="outline">Open Drawer</x-ui::drawer.trigger>
                <x-ui::drawer.content>
                    <div class="mx-auto w-full max-w-sm">
                        <x-ui::drawer.header>
                            <x-ui::drawer.title>Move Goal</x-ui::drawer.title>
                            <x-ui::drawer.description>Set your daily activity goal.</x-ui::drawer.description>
                        </x-ui::drawer.header>
                        <div class="p-4 text-center text-6xl font-bold tracking-tighter">350</div>
                        <x-ui::drawer.footer>
                            <x-ui::button>Submit</x-ui::button>
                            <x-ui::drawer.close variant="outline">Cancel</x-ui::drawer.close>
                        </x-ui::drawer.footer>
                    </div>
                </x-ui::drawer.content>
            </x-ui::drawer>
            <x-ui::drawer name="side" swipe-direction="right">
                <x-ui::drawer.trigger variant="outline">Right Drawer</x-ui::drawer.trigger>
                <x-ui::drawer.content>
                    <x-ui::drawer.header>
                        <x-ui::drawer.title>Settings</x-ui::drawer.title>
                        <x-ui::drawer.description>Swipe right to close.</x-ui::drawer.description>
                    </x-ui::drawer.header>
                    <x-ui::drawer.footer>
                        <x-ui::drawer.close variant="outline">Close</x-ui::drawer.close>
                    </x-ui::drawer.footer>
                </x-ui::drawer.content>
            </x-ui::drawer>
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
