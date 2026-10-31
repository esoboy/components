<?php

/*
 * Navigation Menu on the Navigation page: content opens on hover (closing when the
 * pointer leaves) or on click (staying until dismissed).
 */

const NAV_CONTENT = '[data-slot="navigation-menu-content"]';

it('opens on hover and closes when the pointer leaves', function () {
    visit('/navigation')
        ->hover('[data-slot="navigation-menu-trigger"]:has-text("Getting started")')
        ->assertScript("document.querySelector('".NAV_CONTENT.":popover-open')?.textContent.includes('Introduction') === true")
        ->hover('h2 >> nth=0')
        ->assertScript("document.querySelector('".NAV_CONTENT.":popover-open') === null")
        ->assertNoJavaScriptErrors();
});

it('switches content when another trigger is hovered', function () {
    visit('/navigation')
        ->hover('[data-slot="navigation-menu-trigger"]:has-text("Getting started")')
        ->assertScript("document.querySelector('".NAV_CONTENT.":popover-open') !== null")
        ->hover('[data-slot="navigation-menu-trigger"]:has-text("Components")')
        ->assertScript("document.querySelector('".NAV_CONTENT.":popover-open')?.textContent.includes('Hover Card') === true")
        ->assertScript("document.querySelectorAll('".NAV_CONTENT.":popover-open').length", 1);
});

it('keeps content opened by click after the pointer leaves', function () {
    visit('/navigation')
        ->click('[data-slot="navigation-menu-trigger"]:has-text("Components")')
        ->hover('h2 >> nth=0')
        ->wait(0.5)
        ->assertScript("document.querySelector('".NAV_CONTENT.":popover-open')?.textContent.includes('Hover Card') === true");
});
