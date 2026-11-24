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
    @elseif(request()->boolean('status'))
        <x-ui::button id="base-button">Action</x-ui::button>
        <x-ui::button id="secondary-button" variant="secondary">Action</x-ui::button>
        <x-ui::button id="destructive-button" variant="destructive">Action</x-ui::button>
        @foreach (['neutral', 'info', 'success', 'warning', 'error'] as $status)
            <x-ui::button id="solid-{{ $status }}" :status="$status">Action</x-ui::button>
            <x-ui::button id="outline-{{ $status }}" variant="outline" :status="$status">Action</x-ui::button>
            <x-ui::badge id="badge-{{ $status }}" :status="$status">Label</x-ui::badge>
            <x-ui::badge id="soft-{{ $status }}" variant="secondary" :status="$status">Label</x-ui::badge>
            <x-ui::switch id="switch-{{ $status }}" checked :status="$status" aria-label="Enabled" />
        @endforeach
        <x-ui::alert status="info">
            <x-ui::badge id="nested-badge">Label</x-ui::badge>
        </x-ui::alert>
        <x-ui::alert id="colored-alert" variant="destructive" status="success">
            <x-ui::icon name="check" /><x-ui::alert.title>Done</x-ui::alert.title>
            <x-ui::alert.description>Saved.</x-ui::alert.description>
        </x-ui::alert>
        <x-ui::toast id="colored-toast" variant="destructive" status="warning" :duration="0">
            <x-ui::toast.content><x-ui::icon name="info" /><x-ui::toast.title>Pending</x-ui::toast.title></x-ui::toast.content>
        </x-ui::toast>
        <x-ui::switch id="plain-switch" aria-label="Off" />
        <x-ui::switch id="info-switch-off" status="info" aria-label="Off" />
        <x-ui::badge id="outer-badge" status="warning" class="[--ui-fg:#142536]">
            <x-ui::badge id="inner-badge">Label</x-ui::badge>
        </x-ui::badge>
        <x-ui::button id="custom-outline" variant="outline" status="info" class="[--ui-border:#123456]">Action</x-ui::button>
        <x-ui::checkbox id="invalid-focus" invalid class="[--color-ring:#216953]" aria-label="Invalid choice" />
        <x-ui::button-group>
            <x-ui::button id="grouped-small" size="sm">First</x-ui::button>
            <x-ui::button size="sm">Last</x-ui::button>
        </x-ui::button-group>
        <x-ui::avatar class="[--ui-part-bg:#111111] [&_.ui-avatar-badge]:[--ui-part-bg:#245647]">
            <x-ui::avatar.fallback id="avatar-fallback">A</x-ui::avatar.fallback>
            <x-ui::avatar.badge id="avatar-badge">✓</x-ui::avatar.badge>
        </x-ui::avatar>
        <x-ui::progress :value="32" aria-label="Progress" class="[--ui-track:#ededed] [--ui-fill:#234567]" />
        <x-ui::select id="theme-select" :options="[['value' => 'one', 'label' => 'One']]" initial-value="one"
            class="w-48 [--ui-trigger-bg:#efdedb] [--ui-trigger-fg:#365462] [--ui-panel-bg:#eff1fc] [--ui-option-selected-fg:#563412]" />
        <x-ui::date-picker id="theme-picker" class="w-48 [--ui-trigger-bg:#ecedef] [--ui-panel-bg:#daf1ef]" />
        <x-ui::password-input id="theme-password" placeholder="Password"
            class="[--ui-value-fg:#213548] [--ui-placeholder-fg:#567c98]" />
        <x-ui::dropdown-menu class="[--ui-item-fg:#502434] [--ui-item-hover-bg:#f2ebce] [--ui-item-hover-fg:#594312]">
            <x-ui::dropdown-menu.trigger><x-ui::button id="theme-menu-trigger">Menu</x-ui::button></x-ui::dropdown-menu.trigger>
            <x-ui::dropdown-menu.content><x-ui::dropdown-menu.item id="theme-menu-item">Item</x-ui::dropdown-menu.item></x-ui::dropdown-menu.content>
        </x-ui::dropdown-menu>
        <x-ui::transfer :options="[['value' => 'one', 'label' => 'One']]" class="[--ui-panel-bg:#eff6fb]" />
        <x-ui::tags-input :initial-value="['One']" class="[--ui-tag-bg:#efeabc]" />
        <x-ui::signature id="theme-signature" :initial-value="[[['x' => 0.25, 'y' => 0.5], ['x' => 0.75, 'y' => 0.5]]]"
            class="[--ui-ink:#24795c]" />
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
