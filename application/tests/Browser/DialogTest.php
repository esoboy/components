<?php

/*
 * Dialog on the Overlays page: a native modal <dialog> opened by its trigger, by the
 * server (Ui::dialog) or by wire:model, kept open through Livewire morphs.
 */

it('opens as a modal from its trigger and closes on Escape', function () {
    visit('/overlays')
        ->click('#tour-dialog')
        ->assertScript(opened('#edit-profile'))
        ->assertScript("document.activeElement.closest('dialog')?.id", 'edit-profile')
        ->keys('#name', 'Escape')
        ->assertScript(opened('#edit-profile'), false)
        ->assertScript('document.activeElement.id', 'tour-dialog')
        ->assertNoJavaScriptErrors();
});

it('closes from its close button and its footer close', function () {
    $page = visit('/overlays');

    $page->click('#tour-dialog')
        ->click('#edit-profile > button[command="close"]')
        ->assertScript(opened('#edit-profile'), false);

    $page->click('#tour-dialog')
        ->click('#edit-profile [data-slot="dialog-footer"] button[command="close"]')
        ->assertScript(opened('#edit-profile'), false);
});

it('stays open through a failed validation and closes when the server saves', function () {
    visit('/overlays')
        ->click('#tour-dialog')
        ->clear('#name')
        ->click('Save changes')
        ->assertSeeIn('#edit-profile', 'The name field is required.')
        ->assertScript(opened('#edit-profile'))
        ->fill('#name', 'Ada Lovelace')
        ->click('Save changes')
        ->assertScript(opened('#edit-profile'), false)
        ->assertSee('Profile saved')
        ->assertSee('Name is now Ada Lovelace.');
});

it('opens from the server', function () {
    visit('/overlays')
        ->click('Open from server')
        ->assertScript(opened('#edit-profile'));
});

it('binds whether it is open with wire:model', function () {
    visit('/overlays')
        ->click('[commandfor="rename"][command="show-modal"]')
        ->assertScript(opened('#rename'))
        ->assertSee('renaming: true')
        ->click('#rename button[type="submit"]')
        ->assertScript(opened('#rename'), false)
        ->assertSee('renaming: false')
        ->assertSee('Renamed');
});
