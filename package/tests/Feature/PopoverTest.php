<?php

/*
 * Popover, Tooltip and Dropdown Menu: a trigger and its content are linked by id
 * and placed with CSS anchor positioning.
 */

it('links a popover trigger to its content', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::popover>
            <x-ui::popover.trigger variant="outline">Open</x-ui::popover.trigger>
            <x-ui::popover.content align="start" side-offset="8">
                <x-ui::popover.header>
                    <x-ui::popover.title>Dimensions</x-ui::popover.title>
                    <x-ui::popover.description>Set them.</x-ui::popover.description>
                </x-ui::popover.header>
            </x-ui::popover.content>
        </x-ui::popover>
        BLADE);

    [$id] = idsOf($html, 'commandfor');

    expect($id)->toStartWith('popover-')
        ->and($html)->toContain("<button type=\"button\" style=\"anchor-name: --{$id};\" data-slot=\"button\" data-variant=\"outline\" data-size=\"default\" commandfor=\"{$id}\" command=\"toggle-popover\">Open</button>")
        ->and($html)->toContain("<div id=\"{$id}\" popover=\"auto\" data-slot=\"popover-content\" data-side=\"bottom\" data-align=\"start\" style=\"position-anchor: --{$id}; --side-offset: 8px\">")
        ->and($html)->toContain('<h2 data-slot="popover-title">Dimensions</h2>')
        ->and($html)->toContain('<p data-slot="popover-description">Set them.</p>');
});

it('gives every popover its own id, nested ones included', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::popover>
            <x-ui::popover.trigger>A</x-ui::popover.trigger>
            <x-ui::popover.content>
                <x-ui::popover>
                    <x-ui::popover.trigger>B</x-ui::popover.trigger>
                    <x-ui::popover.content>b</x-ui::popover.content>
                </x-ui::popover>
            </x-ui::popover.content>
        </x-ui::popover>
        BLADE);

    $triggers = idsOf($html, 'commandfor');
    $contents = idsOf($html, 'id');

    expect($triggers)->toHaveCount(2)
        ->and(array_unique($triggers))->toHaveCount(2)
        ->and($contents)->toBe($triggers);
});

it('renders a tooltip shown on hover', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::tooltip>
            <x-ui::tooltip.trigger variant="outline">Hover</x-ui::tooltip.trigger>
            <x-ui::tooltip.content>Add to library</x-ui::tooltip.content>
        </x-ui::tooltip>
        BLADE);

    [$id] = idsOf($html, 'data-tooltip');

    expect($html)->toContain("style=\"anchor-name: --{$id};\" data-slot=\"button\" data-variant=\"outline\" data-size=\"default\" data-tooltip=\"{$id}\" aria-describedby=\"{$id}\">Hover</button>")
        ->and($html)->toContain("<div id=\"{$id}\" popover=\"manual\" role=\"tooltip\" data-slot=\"tooltip-content\" data-side=\"top\" data-align=\"center\" style=\"position-anchor: --{$id}; --side-offset: 0px\">Add to library</div>");
});

it('renders a dropdown menu whose items close the root menu', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::dropdown-menu>
            <x-ui::dropdown-menu.trigger>Open</x-ui::dropdown-menu.trigger>
            <x-ui::dropdown-menu.content align="start">
                <x-ui::dropdown-menu.label inset>Account</x-ui::dropdown-menu.label>
                <x-ui::dropdown-menu.group>
                    <x-ui::dropdown-menu.item wire:click="profile">Profile <x-ui::dropdown-menu.shortcut>⌘P</x-ui::dropdown-menu.shortcut></x-ui::dropdown-menu.item>
                    <x-ui::dropdown-menu.item href="/billing">Billing</x-ui::dropdown-menu.item>
                </x-ui::dropdown-menu.group>
                <x-ui::dropdown-menu.separator />
                <x-ui::dropdown-menu.sub>
                    <x-ui::dropdown-menu.sub-trigger>Invite</x-ui::dropdown-menu.sub-trigger>
                    <x-ui::dropdown-menu.sub-content>
                        <x-ui::dropdown-menu.item>Email</x-ui::dropdown-menu.item>
                    </x-ui::dropdown-menu.sub-content>
                </x-ui::dropdown-menu.sub>
                <x-ui::dropdown-menu.item variant="destructive">Delete</x-ui::dropdown-menu.item>
            </x-ui::dropdown-menu.content>
        </x-ui::dropdown-menu>
        BLADE);

    [$menu, $sub] = array_values(array_unique(idsOf($html, 'commandfor')));

    expect($menu)->toStartWith('dropdown-menu-page-')
        ->and($sub)->toStartWith('dropdown-menu-sub-')
        ->and($html)->toContain("style=\"anchor-name: --{$menu};\" data-slot=\"button\" data-variant=\"default\" data-size=\"default\" commandfor=\"{$menu}\" command=\"toggle-popover\" aria-haspopup=\"menu\">Open</button>")
        ->and($html)->toContain("<div id=\"{$menu}\" popover=\"auto\" role=\"menu\" tabindex=\"-1\" data-slot=\"dropdown-menu-content\" data-side=\"bottom\" data-align=\"start\"")
        ->and($html)->toContain('<div data-slot="dropdown-menu-label" data-inset="data-inset">Account</div>')
        ->and($html)->toContain("<button type=\"button\" commandfor=\"{$menu}\" command=\"hide-popover\" role=\"menuitem\" data-slot=\"dropdown-menu-item\" data-variant=\"default\" wire:click=\"profile\">")
        ->and($html)->toContain('<span data-slot="dropdown-menu-shortcut">⌘P</span>')
        ->and($html)->toContain('<a role="menuitem" data-slot="dropdown-menu-item" data-variant="default" href="/billing">Billing</a>')
        ->and($html)->toContain('<div role="separator" data-slot="dropdown-menu-separator"></div>')
        ->and($html)->toContain("aria-haspopup=\"menu\" data-slot=\"dropdown-menu-sub-trigger\" commandfor=\"{$sub}\" command=\"toggle-popover\" style=\"anchor-name: --{$sub}\">")
        ->and($html)->toContain("<div id=\"{$sub}\" popover=\"auto\" role=\"menu\" tabindex=\"-1\" data-slot=\"dropdown-menu-sub-content\" data-side=\"right\" data-align=\"start\"")
        ->and($html)->toContain('data-variant="destructive"');

    // Items inside the submenu close the root menu too.
    expect(substr_count($html, "commandfor=\"{$menu}\" command=\"hide-popover\""))->toBe(3);
});

it('binds checkbox and radio items with wire:model', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::dropdown-menu.checkbox-item wire:model.live="statusBar" class="w-full">Status Bar</x-ui::dropdown-menu.checkbox-item>
        <x-ui::dropdown-menu.radio-group wire:model.live="position">
            <x-ui::dropdown-menu.radio-item value="top">Top</x-ui::dropdown-menu.radio-item>
        </x-ui::dropdown-menu.radio-group>
        BLADE);

    expect($html)->toContain('<label data-slot="dropdown-menu-checkbox-item" class="w-full">')
        ->and($html)->toContain('<input type="checkbox" role="menuitemcheckbox" wire:model.live="statusBar">')
        ->and($html)->toContain('<ui-radio-group role="group" data-slot="dropdown-menu-radio-group" wire:model.live="position">')
        ->and($html)->toContain('<input type="radio" role="menuitemradio" value="top">');
});
