<?php

use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|min:3')]
    public string $name = 'Pedro Duarte';

    public bool $statusBar = true;

    public string $position = 'bottom';

    public int $deleted = 0;

    public function save(): void
    {
        $this->validate();

        $this->dialog('edit-profile')->close();
        $this->toast('Profile saved', description: "Name is now {$this->name}.", type: 'success');
    }

    public function openFromServer(): void
    {
        $this->dialog('edit-profile')->open();
    }

    public function delete(): void
    {
        $this->deleted++;
        $this->toast('Deleted', type: 'error');
    }
};
?>

<div class="mx-auto max-w-3xl space-y-10 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Dialog</h2>
        <div class="flex flex-wrap gap-3">
            <x-ui::dialog name="edit-profile">
                <x-ui::dialog.trigger variant="outline">Edit profile</x-ui::dialog.trigger>
                <x-ui::dialog.content class="sm:max-w-[425px]">
                    <form wire:submit="save" class="grid gap-4">
                        <x-ui::dialog.header>
                            <x-ui::dialog.title>Edit profile</x-ui::dialog.title>
                            <x-ui::dialog.description>Make changes to your profile here. Click save when you're done.</x-ui::dialog.description>
                        </x-ui::dialog.header>
                        <x-ui::field>
                            <x-ui::field.label for="name">Name</x-ui::field.label>
                            <x-ui::input id="name" wire:model="name" />
                        </x-ui::field>
                        <x-ui::dialog.footer>
                            <x-ui::dialog.close variant="outline">Cancel</x-ui::dialog.close>
                            <x-ui::button type="submit">Save changes</x-ui::button>
                        </x-ui::dialog.footer>
                    </form>
                </x-ui::dialog.content>
            </x-ui::dialog>

            <x-ui::button variant="secondary" wire:click="openFromServer">Open from server</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Alert Dialog &amp; wire:confirm</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::alert-dialog name="delete-account">
                <x-ui::alert-dialog.trigger variant="outline">Show alert dialog</x-ui::alert-dialog.trigger>
                <x-ui::alert-dialog.content>
                    <x-ui::alert-dialog.header>
                        <x-ui::alert-dialog.title>Are you absolutely sure?</x-ui::alert-dialog.title>
                        <x-ui::alert-dialog.description>This action cannot be undone. This will permanently delete your account and remove your data from our servers.</x-ui::alert-dialog.description>
                    </x-ui::alert-dialog.header>
                    <x-ui::alert-dialog.footer>
                        <x-ui::alert-dialog.cancel>Cancel</x-ui::alert-dialog.cancel>
                        <x-ui::alert-dialog.action wire:click="delete">Continue</x-ui::alert-dialog.action>
                    </x-ui::alert-dialog.footer>
                </x-ui::alert-dialog.content>
            </x-ui::alert-dialog>

            <x-ui::button variant="destructive" wire:click="delete" wire:confirm="Delete this item?">Delete with wire:confirm</x-ui::button>
            <span class="text-sm text-muted-foreground">deleted: <span id="deleted">{{ $deleted }}</span></span>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Sheet</h2>
        <div class="flex flex-wrap gap-3">
            @foreach (['top', 'right', 'bottom', 'left'] as $side)
                <x-ui::sheet name="sheet-{{ $side }}">
                    <x-ui::sheet.trigger variant="outline">{{ ucfirst($side) }}</x-ui::sheet.trigger>
                    <x-ui::sheet.content :side="$side">
                        <x-ui::sheet.header>
                            <x-ui::sheet.title>Edit profile</x-ui::sheet.title>
                            <x-ui::sheet.description>Make changes to your profile here.</x-ui::sheet.description>
                        </x-ui::sheet.header>
                        <x-ui::sheet.footer>
                            <x-ui::sheet.close variant="outline">Close</x-ui::sheet.close>
                        </x-ui::sheet.footer>
                    </x-ui::sheet.content>
                </x-ui::sheet>
            @endforeach
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Popover &amp; Tooltip</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::popover>
                <x-ui::popover.trigger variant="outline">Open popover</x-ui::popover.trigger>
                <x-ui::popover.content align="start">
                    <x-ui::popover.header>
                        <x-ui::popover.title>Dimensions</x-ui::popover.title>
                        <x-ui::popover.description>Set the dimensions for the layer.</x-ui::popover.description>
                    </x-ui::popover.header>
                </x-ui::popover.content>
            </x-ui::popover>

            <x-ui::tooltip>
                <x-ui::tooltip.trigger variant="outline">Hover</x-ui::tooltip.trigger>
                <x-ui::tooltip.content>Add to library</x-ui::tooltip.content>
            </x-ui::tooltip>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Dropdown Menu</h2>
        <x-ui::dropdown-menu>
            <x-ui::dropdown-menu.trigger variant="outline">Open</x-ui::dropdown-menu.trigger>
            <x-ui::dropdown-menu.content class="w-56" align="start">
                <x-ui::dropdown-menu.label>My Account</x-ui::dropdown-menu.label>
                <x-ui::dropdown-menu.group>
                    <x-ui::dropdown-menu.item>Profile <x-ui::dropdown-menu.shortcut>⇧⌘P</x-ui::dropdown-menu.shortcut></x-ui::dropdown-menu.item>
                    <x-ui::dropdown-menu.item>Billing</x-ui::dropdown-menu.item>
                    <x-ui::dropdown-menu.item disabled>Settings</x-ui::dropdown-menu.item>
                </x-ui::dropdown-menu.group>
                <x-ui::dropdown-menu.separator />
                <x-ui::dropdown-menu.sub>
                    <x-ui::dropdown-menu.sub-trigger>Invite users</x-ui::dropdown-menu.sub-trigger>
                    <x-ui::dropdown-menu.sub-content>
                        <x-ui::dropdown-menu.item>Email</x-ui::dropdown-menu.item>
                        <x-ui::dropdown-menu.item>Message</x-ui::dropdown-menu.item>
                    </x-ui::dropdown-menu.sub-content>
                </x-ui::dropdown-menu.sub>
                <x-ui::dropdown-menu.separator />
                <x-ui::dropdown-menu.checkbox-item wire:model.live="statusBar">Status Bar</x-ui::dropdown-menu.checkbox-item>
                <x-ui::dropdown-menu.separator />
                <x-ui::dropdown-menu.radio-group wire:model.live="position">
                    <x-ui::dropdown-menu.radio-item value="top">Top</x-ui::dropdown-menu.radio-item>
                    <x-ui::dropdown-menu.radio-item value="bottom">Bottom</x-ui::dropdown-menu.radio-item>
                </x-ui::dropdown-menu.radio-group>
                <x-ui::dropdown-menu.separator />
                <x-ui::dropdown-menu.item variant="destructive" wire:click="delete">Delete</x-ui::dropdown-menu.item>
            </x-ui::dropdown-menu.content>
        </x-ui::dropdown-menu>
        <p class="text-sm text-muted-foreground">status bar: <span id="status-bar">{{ $statusBar ? 'on' : 'off' }}</span>, position: <span id="position">{{ $position }}</span></p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Toast</h2>
        <div class="flex flex-wrap gap-3">
            <x-ui::button variant="outline" wire:click="$dispatch('ui-toast', { message: 'Event has been created', description: 'Sunday, December 03, 2023 at 9:00 AM' })">Default</x-ui::button>
            @foreach (['success', 'info', 'warning', 'error'] as $type)
                <x-ui::button variant="outline" wire:click="$dispatch('ui-toast', { message: '{{ ucfirst($type) }} toast', type: '{{ $type }}' })">{{ ucfirst($type) }}</x-ui::button>
            @endforeach
        </div>
    </section>
</div>
