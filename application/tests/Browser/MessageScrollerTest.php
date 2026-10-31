<?php

/*
 * Message Scroller on the Chat page: it starts at the latest message, follows new ones,
 * keeps its place when earlier ones load above, and a button returns to the end.
 */

const VIEWPORT = '[data-slot="message-scroller-viewport"]';

function atEnd(): string
{
    return "(viewport => viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight < 16)(document.querySelector('".VIEWPORT."'))";
}

it('starts at the latest message', function () {
    visit('/chat')
        ->assertScript(atEnd())
        ->assertNoJavaScriptErrors();
});

it('follows a message sent', function () {
    visit('/chat')
        ->fill('[wire\\:model="draft"]', 'Replacement, please.')
        ->keys('[wire\\:model="draft"]', 'Enter')
        ->assertSee('Replacement, please.')
        ->assertScript("document.querySelector('".VIEWPORT."').scrollTop > 0")
        ->assertValue('[wire\\:model="draft"]', '');
});

it('keeps its place when earlier messages load above', function () {
    $page = visit('/chat');

    $page->script("document.querySelector('".VIEWPORT."').scrollTop = 0");
    $page->assertScript("document.querySelector('".VIEWPORT."').scrollTop", 0);

    $page->click('Load earlier messages')
        ->assertSee('Earlier message 3.1 from a previous conversation.')
        ->assertScript("document.querySelector('".VIEWPORT."').scrollTop > 0");
});

it('returns to the end from its button', function () {
    $page = visit('/chat')->resize(1280, 480)->assertScript(atEnd());

    // Scrolling up from the end lets go of the live edge, which shows the button.
    $page->wait(0.5)->script("document.querySelector('".VIEWPORT."').scrollTop = 0");

    $page->assertAttribute('[data-message-scroller-button]', 'data-active', 'true')
        ->click('[data-message-scroller-button]')
        ->assertScript(atEnd());
});
