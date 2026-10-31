<?php

/*
 * Display components on the General page: count badges follow the server, the QR code
 * is redrawn for a new value, and avatars fall back when their image fails.
 */

const NOTIFICATIONS = '[data-slot="badge-wrapper"]:has([aria-label="Notifications"]) [data-slot="badge"]';

it('shows a count badge, capped at its max and hidden at zero', function () {
    visit('/general')
        ->assertSee('99+')
        ->assertSee('9+')
        ->assertSeeIn(NOTIFICATIONS, '5')
        ->click('Notify')
        ->assertSeeIn(NOTIFICATIONS, '6')
        ->click('Clear')
        ->assertNotPresent(NOTIFICATIONS)
        ->assertNoJavaScriptErrors();
});

it('redraws the QR code for a new value', function () {
    $page = visit('/general');

    $before = $page->script("document.querySelector('[data-slot=\"qr-code\"] path').getAttribute('d')");

    $page->fill('[wire\\:model\\.live\\.debounce\\.300ms="link"]', 'https://esoboy.com/orders/2048')
        ->assertScript("document.querySelector('[data-slot=\"qr-code\"] path').getAttribute('d') !== ".json_encode($before));
});

it('hides an avatar image that fails, uncovering its fallback', function () {
    $page = visit('/general');

    $page->script("document.querySelector('[data-slot=\"avatar-image\"]').src = '/missing.png'");

    $page->assertScript("document.querySelector('[data-slot=\"avatar-image\"]').hidden");
});
