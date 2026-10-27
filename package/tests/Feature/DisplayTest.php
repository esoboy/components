<?php

/*
 * Badge, Avatar, Card, Kbd, Spinner, Skeleton, Empty, Item, Alert, Button Group.
 */

it('renders badges as spans or links', function () {
    $this->blade('<x-ui::badge>New</x-ui::badge>')
        ->assertSee('<span data-slot="badge" data-variant="default">New</span>', false);

    $this->blade('<x-ui::badge variant="outline" href="/inbox">Inbox</x-ui::badge>')
        ->assertSee('<a data-slot="badge" data-variant="outline" href="/inbox">Inbox</a>', false);
});

it('renders an avatar with its parts', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::avatar.group>
            <x-ui::avatar size="lg">
                <x-ui::avatar.image src="/me.png" alt="Me" />
                <x-ui::avatar.fallback>ME</x-ui::avatar.fallback>
                <x-ui::avatar.badge />
            </x-ui::avatar>
            <x-ui::avatar.group-count>+3</x-ui::avatar.group-count>
        </x-ui::avatar.group>
        BLADE);

    $view->assertSee('<div data-slot="avatar-group">', false)
        ->assertSee('<span data-slot="avatar" data-size="lg">', false)
        ->assertSee('<img data-slot="avatar-image" src="/me.png" alt="Me">', false)
        ->assertSee('<span data-slot="avatar-fallback">ME</span>', false)
        ->assertSee('<span data-slot="avatar-badge"></span>', false)
        ->assertSee('<div data-slot="avatar-group-count">+3</div>', false);
});

it('renders a card with its parts', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::card size="sm">
            <x-ui::card.header>
                <x-ui::card.title>Title</x-ui::card.title>
                <x-ui::card.description>Description</x-ui::card.description>
                <x-ui::card.action>Action</x-ui::card.action>
            </x-ui::card.header>
            <x-ui::card.content>Content</x-ui::card.content>
            <x-ui::card.footer>Footer</x-ui::card.footer>
        </x-ui::card>
        BLADE);

    $view->assertSee('<div data-slot="card" data-size="sm">', false)
        ->assertSeeInOrder([
            '<div data-slot="card-header">',
            '<div data-slot="card-title">Title</div>',
            '<div data-slot="card-description">Description</div>',
            '<div data-slot="card-action">Action</div>',
            '<div data-slot="card-content">Content</div>',
            '<div data-slot="card-footer">Footer</div>',
        ], false);
});

it('renders an alert', function () {
    $this->blade(<<<'BLADE'
        <x-ui::alert variant="destructive">
            <x-ui::alert.title>Failed</x-ui::alert.title>
            <x-ui::alert.description>Try again.</x-ui::alert.description>
            <x-ui::alert.action>Retry</x-ui::alert.action>
        </x-ui::alert>
        BLADE)
        ->assertSee('<div role="alert" data-slot="alert" data-variant="destructive">', false)
        ->assertSee('<div data-slot="alert-title">Failed</div>', false)
        ->assertSee('<div data-slot="alert-description">Try again.</div>', false)
        ->assertSee('<div data-slot="alert-action">Retry</div>', false);
});

it('renders kbd, skeleton and a translated spinner', function () {
    $this->blade('<x-ui::kbd.group><x-ui::kbd>⌘</x-ui::kbd><x-ui::kbd>K</x-ui::kbd></x-ui::kbd.group>')
        ->assertSeeInOrder(['<kbd data-slot="kbd-group">', '<kbd data-slot="kbd">⌘</kbd>', '<kbd data-slot="kbd">K</kbd>', '</kbd>'], false);

    $this->blade('<x-ui::skeleton class="h-4 w-32" />')
        ->assertSee('<div data-slot="skeleton" class="h-4 w-32"></div>', false);

    app()->setLocale('zh_CN');

    $spinner = (string) $this->blade('<x-ui::spinner class="size-6" />');

    expect($spinner)
        ->toContain('role="status"')
        ->toContain('class="size-6" data-slot="spinner" aria-label="加载中"')
        ->toContain('<path d="M21 12a9 9 0 1 1-6.219-8.56"/>')
        ->not->toContain('aria-hidden');
});

it('renders an empty state', function () {
    $this->blade(<<<'BLADE'
        <x-ui::empty>
            <x-ui::empty.header>
                <x-ui::empty.media variant="icon">!</x-ui::empty.media>
                <x-ui::empty.title>Nothing</x-ui::empty.title>
                <x-ui::empty.description>Create one.</x-ui::empty.description>
            </x-ui::empty.header>
            <x-ui::empty.content>Actions</x-ui::empty.content>
        </x-ui::empty>
        BLADE)
        ->assertSee('<div data-slot="empty">', false)
        ->assertSee('<div data-slot="empty-icon" data-variant="icon">!</div>', false)
        ->assertSee('<div data-slot="empty-title">Nothing</div>', false)
        ->assertSee('<p data-slot="empty-description">Create one.</p>', false)
        ->assertSee('<div data-slot="empty-content">Actions</div>', false);
});

it('renders items as divs or links', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::item.group>
            <x-ui::item variant="outline" size="sm">
                <x-ui::item.media variant="icon">i</x-ui::item.media>
                <x-ui::item.content>
                    <x-ui::item.title>Title</x-ui::item.title>
                    <x-ui::item.description>Description</x-ui::item.description>
                </x-ui::item.content>
                <x-ui::item.actions>Go</x-ui::item.actions>
            </x-ui::item>
            <x-ui::item.separator />
            <x-ui::item href="/profile">
                <x-ui::item.header>Header</x-ui::item.header>
                <x-ui::item.footer>Footer</x-ui::item.footer>
            </x-ui::item>
        </x-ui::item.group>
        BLADE);

    $view->assertSee('<div role="list" data-slot="item-group">', false)
        ->assertSee('<div data-slot="item" data-variant="outline" data-size="sm">', false)
        ->assertSee('<div data-slot="item-media" data-variant="icon">i</div>', false)
        ->assertSee('<div data-slot="item-title">Title</div>', false)
        ->assertSee('<p data-slot="item-description">Description</p>', false)
        ->assertSee('<div data-slot="item-actions">Go</div>', false)
        ->assertSee('<div data-slot="item-separator" data-orientation="horizontal" role="none"></div>', false)
        ->assertSee('<a data-slot="item" data-variant="default" data-size="default" href="/profile">', false)
        ->assertSee('<div data-slot="item-header">Header</div>', false);
});

it('renders a button group', function () {
    $this->blade(<<<'BLADE'
        <x-ui::button-group orientation="vertical">
            <x-ui::button-group.text>https://</x-ui::button-group.text>
            <x-ui::button-group.separator />
            <x-ui::button variant="outline">Go</x-ui::button>
        </x-ui::button-group>
        BLADE)
        ->assertSee('<div role="group" data-slot="button-group" data-orientation="vertical">', false)
        ->assertSee('<div data-slot="button-group-text">https://</div>', false)
        ->assertSee('<div data-slot="button-group-separator" data-orientation="vertical" role="none"></div>', false);
});
