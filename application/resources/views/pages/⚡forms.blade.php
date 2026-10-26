<?php

use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:10')]
    public string $bio = '';

    #[Validate('required|in:admin,agent')]
    public string $role = '';

    #[Validate('required|in:starter,pro')]
    public string $plan = '';

    #[Validate('accepted')]
    public bool $terms = false;

    public bool $notifications = true;

    #[Validate('required|array|min:1')]
    public array $channels = [];

    public ?string $saved = null;

    public function save(): void
    {
        $this->validate();

        $this->saved = now()->toTimeString();
    }
};
?>

<div class="mx-auto max-w-xl p-10">
    <form wire:submit="save">
        <x-ui::field.set>
            <x-ui::field.legend>Profile</x-ui::field.legend>
            <x-ui::field.description>Livewire validation shows up under each control automatically.</x-ui::field.description>

            <x-ui::field.group>
                <x-ui::field>
                    <x-ui::field.label for="email">Email</x-ui::field.label>
                    <x-ui::input id="email" type="email" wire:model.live.blur="email" placeholder="you@example.com" />
                    <x-ui::field.description>wire:model.live.blur: validated as soon as you leave the field.</x-ui::field.description>
                </x-ui::field>

                <x-ui::field>
                    <x-ui::field.label for="bio">Bio</x-ui::field.label>
                    <x-ui::textarea id="bio" wire:model="bio" placeholder="Tell us about yourself" />
                </x-ui::field>

                <x-ui::field>
                    <x-ui::field.label for="role">Role</x-ui::field.label>
                    <x-ui::native-select id="role" wire:model="role" class="w-full">
                        <x-ui::native-select.option value="">Choose…</x-ui::native-select.option>
                        <x-ui::native-select.option value="admin">Admin</x-ui::native-select.option>
                        <x-ui::native-select.option value="agent">Agent</x-ui::native-select.option>
                        <x-ui::native-select.option value="other">Other (fails)</x-ui::native-select.option>
                    </x-ui::native-select>
                </x-ui::field>

                <x-ui::field.set>
                    <x-ui::field.legend variant="label">Plan</x-ui::field.legend>
                    <x-ui::radio-group wire:model="plan">
                        <x-ui::field orientation="horizontal">
                            <x-ui::radio-group.item id="plan-starter" value="starter" />
                            <x-ui::field.label for="plan-starter">Starter</x-ui::field.label>
                        </x-ui::field>
                        <x-ui::field orientation="horizontal">
                            <x-ui::radio-group.item id="plan-pro" value="pro" />
                            <x-ui::field.label for="plan-pro">Pro</x-ui::field.label>
                        </x-ui::field>
                    </x-ui::radio-group>
                </x-ui::field.set>

                <x-ui::field.set>
                    <x-ui::field.legend variant="label">Channels</x-ui::field.legend>
                    <x-ui::field orientation="horizontal">
                        <x-ui::checkbox id="channel-email" value="email" wire:model="channels" />
                        <x-ui::field.label for="channel-email">Email</x-ui::field.label>
                    </x-ui::field>
                    <x-ui::field orientation="horizontal">
                        <x-ui::checkbox id="channel-sms" value="sms" wire:model="channels" />
                        <x-ui::field.label for="channel-sms">SMS</x-ui::field.label>
                    </x-ui::field>
                    <x-ui::field.error name="channels" />
                </x-ui::field.set>

                <x-ui::field.separator />

                <x-ui::field orientation="horizontal">
                    <x-ui::switch id="notifications" wire:model="notifications" />
                    <x-ui::field.label for="notifications">Notifications</x-ui::field.label>
                </x-ui::field>

                <x-ui::field orientation="horizontal">
                    <x-ui::checkbox id="terms" wire:model="terms" />
                    <x-ui::field.label for="terms">Accept terms</x-ui::field.label>
                </x-ui::field>

                <x-ui::field orientation="horizontal">
                    <x-ui::button type="submit">Save</x-ui::button>
                    @if ($saved)
                        <span class="text-sm text-muted-foreground">Saved at {{ $saved }}</span>
                    @endif
                </x-ui::field>
            </x-ui::field.group>
        </x-ui::field.set>
    </form>

    <div class="mt-10 space-y-2">
        <p class="text-sm text-muted-foreground">Outside any field: bound to the same email, never validated.</p>
        <x-ui::input id="email-standalone" wire:model="email" placeholder="standalone" />
    </div>

    <div id="island" class="scheme-dark mt-10 space-y-3 rounded-lg bg-background p-4 text-foreground">
        <p class="text-sm">scheme-dark: always dark, whatever the system uses.</p>
        <x-ui::button>Primary</x-ui::button>
        <x-ui::input placeholder="Input" />
    </div>
</div>
