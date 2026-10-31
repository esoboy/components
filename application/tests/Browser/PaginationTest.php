<?php

/*
 * Pagination on the Navigation page: renders a Laravel paginator whose links page
 * through Livewire.
 */

const PAGINATOR = '#tabs [data-slot="pagination"]';

it('pages through a Livewire paginator', function () {
    visit('/navigation')
        ->assertSee('INV001')
        ->click(PAGINATOR.' button:text-is("2")')
        ->assertSee('INV006')
        ->assertDontSee('INV001')
        ->assertAttribute(PAGINATOR.' [aria-current="page"]', 'data-active', 'data-active')
        ->assertSeeIn(PAGINATOR.' [aria-current="page"]', '2')
        ->assertQueryStringHas('page', '2')
        ->assertNoJavaScriptErrors();
});

it('goes to the next and previous pages', function () {
    visit('/navigation')
        ->assertDisabled(PAGINATOR.' [aria-label="Go to previous page"]')
        ->click(PAGINATOR.' [aria-label="Go to next page"]')
        ->assertSee('INV006')
        ->click(PAGINATOR.' [aria-label="Go to previous page"]')
        ->assertSee('INV001');
});
