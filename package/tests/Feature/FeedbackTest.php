<?php

use Esoboy\Ui\Tests\Fixtures\Notifier;
use Livewire\Livewire;

it('dispatches a toast from a Livewire component', function () {
    Livewire::test(Notifier::class)
        ->call('saved')
        ->assertDispatched('ui-toast', message: 'Saved', description: null, type: 'default', duration: null);
});

it('passes toast options through', function () {
    Livewire::test(Notifier::class)
        ->call('failed')
        ->assertDispatched('ui-toast', message: 'Failed', description: 'Try again.', type: 'error', duration: 8000);
});

it('opens and closes a dialog by name', function () {
    Livewire::test(Notifier::class)
        ->call('edit')
        ->assertDispatched('ui-dialog', name: 'edit', open: true)
        ->call('done')
        ->assertDispatched('ui-dialog', name: 'edit', open: false);
});

it('renders the toaster', function () {
    $this->blade('<x-ui::toaster />')
        ->assertSee('<ol data-slot="toaster" data-position="bottom-right" popover="manual" aria-live="polite"></ol>', false);

    $this->blade('<x-ui::toaster position="top-center" />')
        ->assertSee('data-position="top-center"', false);
});

it('renders the confirm dialog with translated labels', function () {
    app()->setLocale('zh_CN');

    $this->blade('<x-ui::confirm />')
        ->assertSee('<dialog id="ui-confirm" role="alertdialog" data-slot="alert-dialog-content"', false)
        ->assertSee('<h2 id="ui-confirm-title" data-slot="alert-dialog-title">确定要继续吗？</h2>', false)
        ->assertSee('commandfor="ui-confirm" command="close">取消</button>', false)
        ->assertSee('commandfor="ui-confirm" command="close" value="confirm">继续</button>', false);
});
