<?php

/*
 * Hover Card, Context Menu, Menubar, Navigation Menu, Drawer.
 */

it('pairs a hover card trigger with its content', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::hover-card>
            <x-ui::hover-card.trigger variant="link" href="/u/nextjs" :delay="10">@nextjs</x-ui::hover-card.trigger>
            <x-ui::hover-card.content>Card</x-ui::hover-card.content>
        </x-ui::hover-card>
        BLADE);

    [$id] = idsOf($html, 'data-hover-card');

    expect($html)
        ->toContain('<a data-slot="button" data-variant="link" data-size="default" style="anchor-name: --'.$id.';" data-hover-card="'.$id.'" data-delay="10" data-close-delay="300" href="/u/nextjs">@nextjs</a>')
        ->toContain('<div id="'.$id.'" popover="manual" data-slot="hover-card-content" data-side="bottom" data-align="center" style="position-anchor: --'.$id.'; --side-offset: 4px">Card</div>');
});

it('opens a context menu from its trigger area, with dropdown menu items', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::context-menu>
            <x-ui::context-menu.trigger>Right click</x-ui::context-menu.trigger>
            <x-ui::context-menu.content>
                <x-ui::context-menu.item variant="destructive" inset>Delete <x-ui::context-menu.shortcut>⌘⌫</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                <x-ui::context-menu.separator />
                <x-ui::context-menu.checkbox-item wire:model="bookmarks">Bookmarks</x-ui::context-menu.checkbox-item>
            </x-ui::context-menu.content>
        </x-ui::context-menu>
        BLADE);

    [$id] = idsOf($html, 'data-context-menu');

    expect($html)
        ->toContain('<div data-slot="context-menu-trigger" data-context-menu="'.$id.'">Right click</div>')
        ->toContain('<div id="'.$id.'" popover="auto" role="menu" tabindex="-1" data-slot="context-menu-content">')
        ->toContain('<button type="button" commandfor="'.$id.'" command="hide-popover" role="menuitem" data-slot="dropdown-menu-item" data-variant="destructive" data-inset="data-inset">Delete')
        ->toContain('<span data-slot="dropdown-menu-shortcut">⌘⌫</span>')
        ->toContain('<div role="separator" data-slot="dropdown-menu-separator"></div>')
        ->toMatch('/<input(?=[^>]*wire:model="bookmarks")(?=[^>]*role="menuitemcheckbox")/');
});

it('renders a menubar whose triggers toggle their menus', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::menubar>
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>File</x-ui::menubar.trigger>
                <x-ui::menubar.content><x-ui::menubar.item>New Tab</x-ui::menubar.item></x-ui::menubar.content>
            </x-ui::menubar.menu>
        </x-ui::menubar>
        BLADE);

    [$id] = idsOf($html, 'commandfor');

    expect($html)
        ->toContain('<div role="menubar" data-slot="menubar">')
        ->toContain('<button type="button" role="menuitem" aria-haspopup="menu" data-slot="menubar-trigger" commandfor="'.$id.'" command="toggle-popover" style="anchor-name: --'.$id.'">File</button>')
        ->toContain('<div id="'.$id.'" popover="auto" role="menu" tabindex="-1" data-slot="menubar-content" data-side="bottom" data-align="start" style="position-anchor: --'.$id.'; --side-offset: 8px">')
        ->toContain('<button type="button" commandfor="'.$id.'" command="hide-popover" role="menuitem" data-slot="dropdown-menu-item" data-variant="default">New Tab</button>');
});

it('renders a navigation menu with trigger-styled links and aligned content', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::navigation-menu align="center">
            <x-ui::navigation-menu.list>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.trigger>Components</x-ui::navigation-menu.trigger>
                    <x-ui::navigation-menu.content><x-ui::navigation-menu.link href="/tabs" active>Tabs</x-ui::navigation-menu.link></x-ui::navigation-menu.content>
                </x-ui::navigation-menu.item>
                <x-ui::navigation-menu.item><x-ui::navigation-menu.link href="/docs">Docs</x-ui::navigation-menu.link></x-ui::navigation-menu.item>
            </x-ui::navigation-menu.list>
        </x-ui::navigation-menu>
        BLADE);

    [$id] = idsOf($html, 'data-navigation-menu');

    expect($html)
        ->toContain('<nav data-slot="navigation-menu">')
        ->toContain('<ul data-slot="navigation-menu-list">')
        ->toContain('<button type="button" aria-expanded="false" aria-controls="'.$id.'" data-slot="navigation-menu-trigger" data-navigation-menu="'.$id.'" style="anchor-name: --'.$id.'">')
        ->toContain('<div id="'.$id.'" popover="auto" data-slot="navigation-menu-content" data-side="bottom" data-align="center" style="position-anchor: --'.$id.'; --side-offset: 8px">')
        ->toContain('<a data-slot="navigation-menu-link" data-active="data-active" aria-current="page" href="/tabs">Tabs</a>')
        ->toContain('<li data-slot="navigation-menu-item"><a data-slot="navigation-menu-link" href="/docs">Docs</a></li>');
});

it('renders a drawer as a dialog swiped towards its edge', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::drawer name="goal" swipe-direction="right" show-swipe-handle>
            <x-ui::drawer.trigger>Open</x-ui::drawer.trigger>
            <x-ui::drawer.content class="gap-2">
                <x-ui::drawer.header>
                    <x-ui::drawer.title>Move Goal</x-ui::drawer.title>
                    <x-ui::drawer.description>Daily goal.</x-ui::drawer.description>
                </x-ui::drawer.header>
                <x-ui::drawer.footer><x-ui::drawer.close>Cancel</x-ui::drawer.close></x-ui::drawer.footer>
            </x-ui::drawer.content>
        </x-ui::drawer>
        BLADE);

    $view->assertSeeInOrder([
        'commandfor="goal" command="show-modal"',
        '<dialog id="goal" data-slot="drawer-popup" data-swipe-direction="right" data-swipe-axis="x" aria-labelledby="goal-title" aria-describedby="goal-description" wire:ignore.self>',
        '<div data-slot="drawer-swipe-handle" aria-hidden="true"></div>',
        '<div data-slot="drawer-content" class="gap-2">',
        '<div data-slot="drawer-header">',
        '<h2 id="goal-title" data-slot="drawer-title">Move Goal</h2>',
        '<p id="goal-description" data-slot="drawer-description">Daily goal.</p>',
        '<div data-slot="drawer-footer">',
        'commandfor="goal" command="close"',
    ], false);
});
