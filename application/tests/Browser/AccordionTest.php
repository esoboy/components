<?php

/*
 * Accordion and Collapsible on the Layout page: native <details> that open one at a
 * time, and a Collapsible bound with wire:model.
 */

it('opens one item at a time', function () {
    visit('/layout')
        ->assertScript("document.querySelector('[data-slot=\"accordion-item\"][data-value=\"shipping\"]').open")
        ->click('What is your return policy?')
        ->assertScript("document.querySelector('[data-slot=\"accordion-item\"][data-value=\"returns\"]').open")
        ->assertScript("document.querySelector('[data-slot=\"accordion-item\"][data-value=\"shipping\"]').open", false)
        ->assertSee('Returns accepted within 30 days.')
        ->assertNoJavaScriptErrors();
});

it('keeps a disabled item closed', function () {
    $page = visit('/layout');

    // A disabled trigger takes no pointer events, so the click is dispatched to it.
    $page->script("document.querySelector('[data-slot=\"accordion-item\"][data-value=\"support\"] summary').click()");

    $page
        ->assertScript("document.querySelector('[data-slot=\"accordion-item\"][data-value=\"support\"]').open", false);
});

it('binds a collapsible with wire:model', function () {
    visit('/layout')
        ->assertSee('Order #4189 (closed)')
        ->assertScript("document.querySelector('[data-slot=\"collapsible-content\"]').hidden")
        ->click('[data-collapsible-trigger]')
        ->assertSee('Order #4189 (open)')
        ->assertSee('Shipping address: 100 Market St, San Francisco')
        ->click('[data-collapsible-trigger]')
        ->assertSee('Order #4189 (closed)');
});
