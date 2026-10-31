<?php

/*
 * Tabs on the Navigation page: bound with wire:model or uncontrolled, moved with the
 * arrow keys along their orientation.
 */

it('binds the active tab', function () {
    visit('/navigation')
        ->assertSee('Tab: invoices')
        ->click('[role="tab"]:text-is("Account")')
        ->assertSee('Make changes to your account here.')
        ->assertAttribute('[role="tab"]:text-is("Account")', 'aria-selected', 'true')
        ->assertScript("document.querySelector('[role=\"tabpanel\"]:has(table)').hidden")
        ->assertNoJavaScriptErrors();
});

it('moves with the arrow keys, past disabled tabs', function () {
    visit('/navigation')
        ->keys('[role="tab"]:text-is("Invoices")', 'ArrowRight')
        ->assertScript('document.activeElement.textContent.trim()', 'Account')
        ->keys('*:focus', 'ArrowRight')
        ->assertScript('document.activeElement.textContent.trim()', 'Invoices');
});

it('switches uncontrolled line tabs', function () {
    visit('/navigation')
        ->click('[role="tab"]:text-is("Analytics")')
        ->assertSee('Analytics content.')
        ->assertDontSee('Overview content.');
});

it('moves vertical tabs with the up and down keys, activating on Enter as Base UI does', function () {
    visit('/navigation')
        ->keys('[role="tab"]:text-is("General")', 'ArrowDown')
        ->assertScript('document.activeElement.textContent.trim()', 'Security')
        ->assertSee('General settings.')
        ->keys('*:focus', 'Enter')
        ->assertSee('Security settings.');
});
