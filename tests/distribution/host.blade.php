<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @vite('resources/css/app.css')
    @unless(request()->boolean('editors'))
        @uiScript
    @endunless
    @livewireStyles
</head>
<body>
    @if(request()->boolean('editors'))
        <x-ui::editor id="document-one" initial-value="One" />
        <x-ui::editor id="document-two" initial-value="Two" />
        <a href="/ui-package-test" wire:navigate>General components</a>
    @elseif(request()->boolean('state'))
        <livewire:state-probe />
    @elseif(request()->boolean('alignment'))
        @php($choices = array_map(fn ($i) => ['value' => $i, 'label' => 'Choice '.$i], range(1, 24)))
        <main class="mx-auto grid w-72 gap-12 py-[25vh]">
            <x-ui::combobox id="inline-search" :options="$choices" :initial-value="18" align-item-with-trigger />
            <x-ui::combobox id="popup-search" :options="$choices" :initial-value="24" align-item-with-trigger search-in-popup />
            <x-ui::combobox id="multi-search" :options="$choices" :initial-value="[2, 18]" align-item-with-trigger multiple />
        </main>
    @else
        <livewire:package-probe />
        <section id="slot-isolation">
            <div data-slot="separator">Host separator</div>
            <a data-slot="host-action" href="#slot-isolation" aria-disabled="true">Host action</a>
            <x-ui::separator id="package-separator" />
            <x-ui::card>
                <div data-slot="separator">Caller content</div>
                <x-ui::separator />
            </x-ui::card>
            <x-ui::bubble variant="default">
                <span data-slot="bubble-content">Caller message</span>
                <x-ui::bubble.content>Package message</x-ui::bubble.content>
            </x-ui::bubble>
        </section>
        <a href="/ui-package-test?editors=1" wire:navigate>Standalone editors</a>
        @foreach (['default', 'outline'] as $variant)
            <div id="sizing-{{ $variant }}" class="grid justify-items-start gap-2 p-2">
                <x-ui::button :variant="$variant">Save draft</x-ui::button>
                <x-ui::toggle :variant="$variant">Bold text</x-ui::toggle>
                <x-ui::toggle-group :variant="$variant" :spacing="0" aria-label="Alignment">
                    <x-ui::toggle-group.item name="align-{{ $variant }}" value="left">Align left</x-ui::toggle-group.item>
                    <x-ui::toggle-group.item name="align-{{ $variant }}" value="right">Align right</x-ui::toggle-group.item>
                </x-ui::toggle-group>
                <x-ui::sidebar.menu-button :variant="$variant" active>Drafts</x-ui::sidebar.menu-button>
            </div>
        @endforeach
    @endif
    @livewireScripts
</body>
</html>
