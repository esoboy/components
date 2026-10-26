@blaze

{{-- Place once in the layout: wire:confirm then asks with this alert dialog instead of the browser's confirm(). --}}
<x-ui::alert-dialog name="ui-confirm">
    <x-ui::alert-dialog.content>
        <x-ui::alert-dialog.header>
            <x-ui::alert-dialog.title>{{ __('ui.confirm') }}</x-ui::alert-dialog.title>
        </x-ui::alert-dialog.header>
        <x-ui::alert-dialog.footer>
            <x-ui::alert-dialog.cancel>{{ __('ui.cancel') }}</x-ui::alert-dialog.cancel>
            <x-ui::alert-dialog.action value="confirm">{{ __('ui.continue') }}</x-ui::alert-dialog.action>
        </x-ui::alert-dialog.footer>
    </x-ui::alert-dialog.content>
</x-ui::alert-dialog>
