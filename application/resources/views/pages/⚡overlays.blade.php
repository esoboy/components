<?php

use Esoboy\Ui\Facades\Ui;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|min:3')]
    public string $name = 'Pedro Duarte';

    public bool $statusBar = true;

    public string $position = 'bottom';

    public bool $bookmarks = true;

    public string $person = 'pedro';

    public int $deleted = 0;

    // Bound with wire:model on the Dialog: open while true.
    public bool $renaming = false;

    public function rename(): void
    {
        $this->validate();

        $this->renaming = false;
        Ui::toast('Renamed', description: "Name is now {$this->name}.", type: 'success');
    }

    public function save(): void
    {
        $this->validate();

        Ui::dialog('edit-profile')->close();
        Ui::toast('Profile saved', description: "Name is now {$this->name}.", type: 'success');
    }

    public function openFromServer(): void
    {
        Ui::dialog('edit-profile')->open();
    }

    public function delete(): void
    {
        $this->deleted++;
        Ui::toast('Deleted', type: 'error', action: ['label' => 'Undo', 'method' => 'undo']);
    }

    public function undo(): void
    {
        $this->deleted--;
        Ui::toast('Restored', type: 'success');
    }

    public function createEvent(): void
    {
        usleep(800_000);
        Ui::toast('Event created', description: 'Sunday, December 03, 2023 at 9:00 AM', type: 'success', id: 'event');
    }

    public function saveAndLeave(): void
    {
        Ui::toast('Saved', description: 'Back on the General page.', type: 'success');

        $this->redirect('/general', navigate: true);
    }

    public function startTour(): void
    {
        Ui::tour('overlays-tour')->start(1);
    }

    public function tourClosed(bool $finished): void
    {
        Ui::toast($finished ? 'Tour finished' : 'Tour skipped', type: $finished ? 'success' : 'info');
    }
};
?>

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Dialog</h2>
        <div class="flex flex-wrap gap-3">
            <x-ui::dialog name="edit-profile">
                <x-ui::dialog.trigger id="tour-dialog" variant="outline">Edit profile</x-ui::dialog.trigger>
                <x-ui::dialog.content>
                    <form wire:submit="save" class="grid gap-4">
                        <x-ui::dialog.header>
                            <x-ui::dialog.title>Edit profile</x-ui::dialog.title>
                            <x-ui::dialog.description>Make changes to your profile here. Click save when you're done.</x-ui::dialog.description>
                        </x-ui::dialog.header>
                        <x-ui::field.group>
                            <x-ui::field>
                                <x-ui::label for="name">Name</x-ui::label>
                                <x-ui::input id="name" wire:model="name" />
                            </x-ui::field>
                            <x-ui::field>
                                <x-ui::label for="username">Username</x-ui::label>
                                <x-ui::input id="username" value="@peduarte" />
                            </x-ui::field>
                        </x-ui::field.group>
                        <x-ui::dialog.footer>
                            <x-ui::dialog.close variant="outline">Cancel</x-ui::dialog.close>
                            <x-ui::button type="submit">Save changes</x-ui::button>
                        </x-ui::dialog.footer>
                    </form>
                </x-ui::dialog.content>
            </x-ui::dialog>

            <x-ui::button variant="secondary" wire:click="openFromServer">Open from server</x-ui::button>

            <x-ui::dialog name="rename" wire:model.live="renaming">
                <x-ui::dialog.trigger variant="outline">Rename (wire:model)</x-ui::dialog.trigger>
                <x-ui::dialog.content class="sm:max-w-sm">
                    <form wire:submit="rename" class="grid gap-4">
                        <x-ui::dialog.header>
                            <x-ui::dialog.title>Rename</x-ui::dialog.title>
                            <x-ui::dialog.description>Open while $renaming is true; saving sets it back to false.</x-ui::dialog.description>
                        </x-ui::dialog.header>
                        <x-ui::field>
                            <x-ui::input wire:model="name" />
                        </x-ui::field>
                        <x-ui::dialog.footer>
                            <x-ui::dialog.close variant="outline">Cancel</x-ui::dialog.close>
                            <x-ui::button type="submit">Save</x-ui::button>
                        </x-ui::dialog.footer>
                    </form>
                </x-ui::dialog.content>
            </x-ui::dialog>
        </div>
        <p class="text-sm text-muted-foreground">renaming: {{ $renaming ? 'true' : 'false' }}</p>
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

            <x-ui::alert-dialog name="delete-chat">
                <x-ui::alert-dialog.trigger variant="destructive">Delete chat</x-ui::alert-dialog.trigger>
                <x-ui::alert-dialog.content size="sm">
                    <x-ui::alert-dialog.header>
                        <x-ui::alert-dialog.media class="bg-destructive/10 text-destructive dark:bg-destructive/20"><x-ui::icon name="trash" /></x-ui::alert-dialog.media>
                        <x-ui::alert-dialog.title>Delete chat?</x-ui::alert-dialog.title>
                        <x-ui::alert-dialog.description>This will permanently delete this chat conversation.</x-ui::alert-dialog.description>
                    </x-ui::alert-dialog.header>
                    <x-ui::alert-dialog.footer>
                        <x-ui::alert-dialog.cancel>Cancel</x-ui::alert-dialog.cancel>
                        <x-ui::alert-dialog.action variant="destructive" wire:click="delete">Delete</x-ui::alert-dialog.action>
                    </x-ui::alert-dialog.footer>
                </x-ui::alert-dialog.content>
            </x-ui::alert-dialog>
            <span class="text-sm text-muted-foreground">deleted: <span id="deleted">{{ $deleted }}</span></span>
        </div>
        <p class="text-sm text-muted-foreground">wire:confirm asks with the layout's &lt;x-ui::confirm /&gt;: .destructive for a red confirm button, .popover at the button, "Question?|Label" to name it.</p>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button variant="outline" wire:click="delete" wire:confirm="Publish this post?|Publish">wire:confirm</x-ui::button>
            <x-ui::button variant="destructive" wire:click="delete" wire:confirm.destructive="Delete this item?">wire:confirm.destructive</x-ui::button>
            <x-ui::button variant="outline" wire:click="delete" wire:confirm.popover="Archive this item?|Archive">wire:confirm.popover</x-ui::button>
            <x-ui::button variant="outline" wire:click="delete" wire:confirm.popover.destructive="Delete this row?">wire:confirm.popover.destructive</x-ui::button>
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
        <h2 class="text-lg font-semibold">Drawer</h2>
        <div class="flex gap-2">
            <x-ui::drawer name="goal" show-swipe-handle>
                <x-ui::drawer.trigger variant="outline">Open Drawer</x-ui::drawer.trigger>
                <x-ui::drawer.content>
                    <div class="mx-auto w-full max-w-sm">
                        <x-ui::drawer.header>
                            <x-ui::drawer.title>Move Goal</x-ui::drawer.title>
                            <x-ui::drawer.description>Set your daily activity goal.</x-ui::drawer.description>
                        </x-ui::drawer.header>
                        <div class="p-4 text-center text-6xl font-bold tracking-tighter">350</div>
                        <x-ui::drawer.footer>
                            <x-ui::button>Submit</x-ui::button>
                            <x-ui::drawer.close variant="outline">Cancel</x-ui::drawer.close>
                        </x-ui::drawer.footer>
                    </div>
                </x-ui::drawer.content>
            </x-ui::drawer>
            <x-ui::drawer name="side" swipe-direction="right">
                <x-ui::drawer.trigger variant="outline">Right Drawer</x-ui::drawer.trigger>
                <x-ui::drawer.content>
                    <x-ui::drawer.header>
                        <x-ui::drawer.title>Settings</x-ui::drawer.title>
                        <x-ui::drawer.description>Swipe right to close.</x-ui::drawer.description>
                    </x-ui::drawer.header>
                    <x-ui::drawer.footer>
                        <x-ui::drawer.close variant="outline">Close</x-ui::drawer.close>
                    </x-ui::drawer.footer>
                </x-ui::drawer.content>
            </x-ui::drawer>
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
        <h2 class="text-lg font-semibold">Hover Card</h2>
        <x-ui::hover-card>
            <x-ui::hover-card.trigger variant="link" href="#" :delay="10" :close-delay="100">Hover Here</x-ui::hover-card.trigger>
            <x-ui::hover-card.content class="flex w-64 flex-col gap-0.5">
                <div class="font-semibold">@nextjs</div>
                <div>The React Framework – created and maintained by @vercel.</div>
                <div class="mt-1 text-xs text-muted-foreground">Joined December 2021</div>
            </x-ui::hover-card.content>
        </x-ui::hover-card>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Dropdown Menu</h2>
        <x-ui::dropdown-menu>
            <x-ui::dropdown-menu.trigger id="tour-dropdown" variant="outline">Open</x-ui::dropdown-menu.trigger>
            <x-ui::dropdown-menu.content class="w-40">
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
        <h2 class="text-lg font-semibold">Context Menu</h2>
        <x-ui::context-menu>
            <x-ui::context-menu.trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">Right click here</x-ui::context-menu.trigger>
            <x-ui::context-menu.content class="w-48">
                <x-ui::context-menu.group>
                    <x-ui::context-menu.item>Back <x-ui::context-menu.shortcut>⌘[</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                    <x-ui::context-menu.item disabled>Forward <x-ui::context-menu.shortcut>⌘]</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                    <x-ui::context-menu.item>Reload <x-ui::context-menu.shortcut>⌘R</x-ui::context-menu.shortcut></x-ui::context-menu.item>
                    <x-ui::context-menu.sub>
                        <x-ui::context-menu.sub-trigger>More Tools</x-ui::context-menu.sub-trigger>
                        <x-ui::context-menu.sub-content>
                            <x-ui::context-menu.item>Save Page...</x-ui::context-menu.item>
                            <x-ui::context-menu.item>Create Shortcut...</x-ui::context-menu.item>
                            <x-ui::context-menu.separator />
                            <x-ui::context-menu.item variant="destructive">Delete</x-ui::context-menu.item>
                        </x-ui::context-menu.sub-content>
                    </x-ui::context-menu.sub>
                </x-ui::context-menu.group>
                <x-ui::context-menu.separator />
                <x-ui::context-menu.checkbox-item wire:model.live="bookmarks">Show Bookmarks</x-ui::context-menu.checkbox-item>
                <x-ui::context-menu.separator />
                <x-ui::context-menu.radio-group wire:model.live="person">
                    <x-ui::context-menu.label inset>People</x-ui::context-menu.label>
                    <x-ui::context-menu.radio-item value="pedro">Pedro Duarte</x-ui::context-menu.radio-item>
                    <x-ui::context-menu.radio-item value="colm">Colm Tuite</x-ui::context-menu.radio-item>
                </x-ui::context-menu.radio-group>
            </x-ui::context-menu.content>
        </x-ui::context-menu>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Menubar</h2>
        <x-ui::menubar class="w-fit">
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>File</x-ui::menubar.trigger>
                <x-ui::menubar.content>
                    <x-ui::menubar.item>New Tab <x-ui::menubar.shortcut>⌘T</x-ui::menubar.shortcut></x-ui::menubar.item>
                    <x-ui::menubar.item>New Window <x-ui::menubar.shortcut>⌘N</x-ui::menubar.shortcut></x-ui::menubar.item>
                    <x-ui::menubar.item disabled>New Incognito Window</x-ui::menubar.item>
                    <x-ui::menubar.separator />
                    <x-ui::menubar.sub>
                        <x-ui::menubar.sub-trigger>Share</x-ui::menubar.sub-trigger>
                        <x-ui::menubar.sub-content>
                            <x-ui::menubar.item>Email link</x-ui::menubar.item>
                            <x-ui::menubar.item>Messages</x-ui::menubar.item>
                        </x-ui::menubar.sub-content>
                    </x-ui::menubar.sub>
                    <x-ui::menubar.separator />
                    <x-ui::menubar.item>Print... <x-ui::menubar.shortcut>⌘P</x-ui::menubar.shortcut></x-ui::menubar.item>
                </x-ui::menubar.content>
            </x-ui::menubar.menu>
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>View</x-ui::menubar.trigger>
                <x-ui::menubar.content class="w-64">
                    <x-ui::menubar.checkbox-item wire:model.live="bookmarks">Always Show Bookmarks Bar</x-ui::menubar.checkbox-item>
                    <x-ui::menubar.separator />
                    <x-ui::menubar.item inset>Reload <x-ui::menubar.shortcut>⌘R</x-ui::menubar.shortcut></x-ui::menubar.item>
                </x-ui::menubar.content>
            </x-ui::menubar.menu>
            <x-ui::menubar.menu>
                <x-ui::menubar.trigger>Profiles</x-ui::menubar.trigger>
                <x-ui::menubar.content>
                    <x-ui::menubar.radio-group wire:model.live="person">
                        <x-ui::menubar.radio-item value="pedro">Pedro</x-ui::menubar.radio-item>
                        <x-ui::menubar.radio-item value="colm">Colm</x-ui::menubar.radio-item>
                    </x-ui::menubar.radio-group>
                </x-ui::menubar.content>
            </x-ui::menubar.menu>
        </x-ui::menubar>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Toast</h2>
        <div id="tour-toast" class="flex flex-wrap gap-3">
            <x-ui::button variant="outline" wire:click="$dispatch('ui-toast', { message: 'Event has been created', description: 'Sunday, December 03, 2023 at 9:00 AM' })">Default</x-ui::button>
            @foreach (['success', 'info', 'warning', 'error'] as $type)
                <x-ui::button variant="outline" wire:click="$dispatch('ui-toast', { message: '{{ ucfirst($type) }} toast', type: '{{ $type }}' })">{{ ucfirst($type) }}</x-ui::button>
            @endforeach
            <x-ui::button variant="outline" wire:click="delete">Delete (with Undo)</x-ui::button>
            <x-ui::button variant="outline" wire:click="$dispatch('ui-toast', { id: 'event', message: 'Creating event…', type: 'loading' }); $wire.createEvent()">Create event (loading)</x-ui::button>
            <x-ui::button variant="outline" wire:click="saveAndLeave">Save and go to General</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tour</h2>
        <div class="flex flex-wrap gap-3">
            <x-ui::button commandfor="overlays-tour" command="--start">Start tour</x-ui::button>
            <x-ui::button variant="outline" wire:click="startTour">Start at step 2 from server</x-ui::button>
            <x-ui::button variant="outline" commandfor="overlays-tour-open" command="--start">Start without blocking</x-ui::button>
        </div>
        <x-ui::tour name="overlays-tour" wire:tour-close="tourClosed($event.detail.finished)">
            <x-ui::tour.step target="#tour-dialog" title="Dialogs" align="start">Open a dialog from its trigger, or from the server with Ui::dialog('name')->open().</x-ui::tour.step>
            <x-ui::tour.step target="#tour-dropdown" title="Menus" side="right" align="start">Menus keep their own anchor while the tour points at them.</x-ui::tour.step>
            <x-ui::tour.step target="#tour-toast" title="Toasts" side="top">Ui::toast() reaches the browser from any Livewire action.</x-ui::tour.step>
            <x-ui::tour.step title="That's all">Close the tour or press Escape at any step; the page hears tour-change and tour-close.</x-ui::tour.step>
        </x-ui::tour>
        <x-ui::tour name="overlays-tour-open" :blocking="false">
            <x-ui::tour.step target="#tour-toast" title="Not blocking" side="bottom">The mask lets clicks through: the Menubar above works while this step is open.</x-ui::tour.step>
        </x-ui::tour>
    </section>
</div>
