<?php

use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public string $link = 'https://esoboy.com/orders/1024';

    #[Validate('required|url')]
    public string $website = '';

    public function increment(): void
    {
        usleep(600_000);

        $this->count++;
    }

    public function check(): void
    {
        $this->validate();
    }
};
?>

@php
$code = <<<'PHP'
<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
PHP;
@endphp

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Theme</h2>
        <p class="text-sm text-muted-foreground">Any control with data-theme-value switches this app's theme; the header uses a Toggle Group.</p>
        <div class="flex flex-wrap items-center gap-4">
            <x-ui::button variant="outline" size="icon" data-theme-value="toggle" aria-label="Toggle theme"><x-ui::icon name="sun-moon" /></x-ui::button>
            <label class="flex items-center gap-2 text-sm"><x-ui::switch data-theme-value="toggle" /> Dark mode</label>
            <x-ui::dropdown-menu>
                <x-ui::dropdown-menu.trigger variant="outline">Theme</x-ui::dropdown-menu.trigger>
                <x-ui::dropdown-menu.content>
                    <x-ui::dropdown-menu.radio-group>
                        <x-ui::dropdown-menu.radio-item value="light" data-theme-value="light">Light</x-ui::dropdown-menu.radio-item>
                        <x-ui::dropdown-menu.radio-item value="dark" data-theme-value="dark">Dark</x-ui::dropdown-menu.radio-item>
                        <x-ui::dropdown-menu.radio-item value="system" data-theme-value="system">System</x-ui::dropdown-menu.radio-item>
                    </x-ui::dropdown-menu.radio-group>
                </x-ui::dropdown-menu.content>
            </x-ui::dropdown-menu>
            <span data-dark-sample class="rounded-md bg-amber-100 px-2 py-1 text-xs text-amber-900 dark:bg-indigo-950 dark:text-indigo-200">dark: utilities follow the choice</span>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">QR Code</h2>
        <x-ui::input wire:model.live.debounce.300ms="link" class="max-w-sm" />
        <div class="flex flex-wrap items-end gap-6">
            <x-ui::qr-code :value="$link" />
            <x-ui::qr-code :value="$link" icon="/demo/photo-2.svg" :size="120" />
            <x-ui::qr-code value="扫码登录 esoboy 管理后台" :size="96" level="L" />
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Variants</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button>Default</x-ui::button>
            <x-ui::button variant="secondary">Secondary</x-ui::button>
            <x-ui::button variant="outline">Outline</x-ui::button>
            <x-ui::button variant="ghost">Ghost</x-ui::button>
            <x-ui::button variant="destructive">Destructive</x-ui::button>
            <x-ui::button variant="link">Link</x-ui::button>
            <x-ui::button href="#" variant="outline">Anchor</x-ui::button>
            <x-ui::button disabled>Disabled</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Sizes</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button size="xs">Extra small</x-ui::button>
            <x-ui::button size="sm">Small</x-ui::button>
            <x-ui::button>Default</x-ui::button>
            <x-ui::button size="lg">Large</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Utility overrides</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::button class="bg-rose-600 hover:bg-rose-700 rounded-full px-8">bg-rose-600 rounded-full px-8</x-ui::button>
            <x-ui::button variant="outline" class="h-12 border-dashed text-base">h-12 border-dashed</x-ui::button>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Livewire loading</h2>
        <div class="flex items-center gap-3">
            <x-ui::button wire:click="increment">Increment</x-ui::button>
            <span class="text-muted-foreground">{{ $count }}</span>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Status colours</h2>
        <div class="flex flex-wrap gap-2">
            @foreach (['default', 'destructive', 'success', 'warning', 'info'] as $variant)
            <x-ui::button :variant="$variant">{{ ucfirst($variant) }}</x-ui::button>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['default', 'destructive', 'success', 'warning', 'info'] as $variant)
            <x-ui::badge :variant="$variant">{{ ucfirst($variant) }}</x-ui::badge>
            @endforeach
        </div>
        <div class="grid gap-3">
            <x-ui::alert variant="success"><x-ui::icon name="circle-check" /><x-ui::alert.title>Payment received</x-ui::alert.title><x-ui::alert.description>Order #4189 has been paid and will ship today.</x-ui::alert.description></x-ui::alert>
            <x-ui::alert variant="warning"><x-ui::icon name="triangle-alert" /><x-ui::alert.title>Low stock</x-ui::alert.title><x-ui::alert.description>Only 3 units of Studio Headphones are left.</x-ui::alert.description></x-ui::alert>
            <x-ui::alert variant="info"><x-ui::icon name="info" /><x-ui::alert.title>Maintenance</x-ui::alert.title><x-ui::alert.description>The dashboard will be read-only on Sunday from 2:00 to 3:00.</x-ui::alert.description></x-ui::alert>
            <x-ui::alert variant="destructive"><x-ui::icon name="circle-x" /><x-ui::alert.title>Payment failed</x-ui::alert.title><x-ui::alert.description>The card was declined. Ask the customer for another method.</x-ui::alert.description></x-ui::alert>
        </div>
        <x-ui::bubble.group>
            @foreach (['success' => 'Refund approved', 'warning' => 'Waiting for the courier', 'info' => 'Mia joined the chat', 'destructive' => 'Message failed to send'] as $variant => $text)
            <x-ui::bubble :variant="$variant"><x-ui::bubble.content>{{ $text }}</x-ui::bubble.content></x-ui::bubble>
            @endforeach
        </x-ui::bubble.group>
        <div class="flex flex-wrap gap-2">
            @foreach (['default', 'success', 'info', 'warning', 'error', 'loading'] as $type)
            <x-ui::button variant="outline" wire:click="$dispatch('ui-toast', { message: '{{ ucfirst($type) }} toast', description: 'Sunday, December 03, 2023 at 9:00 AM', type: '{{ $type }}' })">{{ ucfirst($type) }} toast</x-ui::button>
            @endforeach
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Badge</h2>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui::badge>Badge</x-ui::badge>
            <x-ui::badge variant="secondary">Secondary</x-ui::badge>
            <x-ui::badge variant="destructive">Destructive</x-ui::badge>
            <x-ui::badge variant="outline">Outline</x-ui::badge>
            <x-ui::badge variant="secondary" class="bg-blue-500 text-white dark:bg-blue-600"><x-ui::icon name="badge-check" /> Verified</x-ui::badge>
            <x-ui::badge class="h-5 min-w-5 rounded-full px-1 font-mono tabular-nums">8</x-ui::badge>
            <x-ui::badge href="#" variant="outline">Link</x-ui::badge>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Avatar</h2>
        <div class="flex flex-wrap items-center gap-6">
            <x-ui::avatar>
                <x-ui::avatar.image src="https://github.com/shadcn.png" alt="@shadcn" />
                <x-ui::avatar.fallback>CN</x-ui::avatar.fallback>
            </x-ui::avatar>
            <x-ui::avatar size="lg">
                <x-ui::avatar.image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <x-ui::avatar.fallback>ER</x-ui::avatar.fallback>
                <x-ui::avatar.badge />
            </x-ui::avatar>
            <x-ui::avatar>
                <x-ui::avatar.image id="broken-avatar" src="/missing.png" alt="missing" />
                <x-ui::avatar.fallback>MS</x-ui::avatar.fallback>
            </x-ui::avatar>
            <x-ui::avatar.group class="grayscale">
                <x-ui::avatar><x-ui::avatar.image src="https://github.com/shadcn.png" alt="" /><x-ui::avatar.fallback>CN</x-ui::avatar.fallback></x-ui::avatar>
                <x-ui::avatar><x-ui::avatar.image src="https://github.com/maxleiter.png" alt="" /><x-ui::avatar.fallback>LR</x-ui::avatar.fallback></x-ui::avatar>
                <x-ui::avatar><x-ui::avatar.image src="https://github.com/evilrabbit.png" alt="" /><x-ui::avatar.fallback>ER</x-ui::avatar.fallback></x-ui::avatar>
                <x-ui::avatar.group-count>+3</x-ui::avatar.group-count>
            </x-ui::avatar.group>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Card</h2>
        <x-ui::card class="w-full max-w-sm">
            <x-ui::card.header>
                <x-ui::card.title>Login to your account</x-ui::card.title>
                <x-ui::card.description>Enter your email below to login to your account</x-ui::card.description>
                <x-ui::card.action>
                    <x-ui::button variant="link">Sign Up</x-ui::button>
                </x-ui::card.action>
            </x-ui::card.header>
            <x-ui::card.content>
                <div class="flex flex-col gap-6">
                    <div class="grid gap-2">
                        <x-ui::label for="email">Email</x-ui::label>
                        <x-ui::input id="email" type="email" placeholder="m@example.com" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center">
                            <x-ui::label for="password">Password</x-ui::label>
                            <a href="#" class="ml-auto inline-block text-sm underline-offset-4 hover:underline">Forgot your password?</a>
                        </div>
                        <x-ui::input id="password" type="password" />
                    </div>
                </div>
            </x-ui::card.content>
            <x-ui::card.footer class="flex-col gap-2">
                <x-ui::button type="submit" class="w-full">Login</x-ui::button>
                <x-ui::button variant="outline" class="w-full">Login with Google</x-ui::button>
            </x-ui::card.footer>
        </x-ui::card>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Alert</h2>
        <div class="grid w-full max-w-xl gap-4">
            <x-ui::alert>
                <x-ui::icon name="circle-check" />
                <x-ui::alert.title>Payment successful</x-ui::alert.title>
                <x-ui::alert.description>Your payment of $29.99 has been processed. A receipt has been sent to your email address.</x-ui::alert.description>
            </x-ui::alert>
            <x-ui::alert variant="destructive">
                <x-ui::icon name="circle-alert" />
                <x-ui::alert.title>Payment failed</x-ui::alert.title>
                <x-ui::alert.description>Your payment could not be processed. Please check your payment method and try again.</x-ui::alert.description>
                <x-ui::alert.action><x-ui::button size="xs" variant="outline">Retry</x-ui::button></x-ui::alert.action>
            </x-ui::alert>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Kbd, Spinner, Skeleton</h2>
        <div class="flex flex-wrap items-center gap-6">
            <x-ui::kbd.group>
                <x-ui::kbd>⌘</x-ui::kbd>
                <x-ui::kbd>⇧</x-ui::kbd>
                <x-ui::kbd>⌥</x-ui::kbd>
                <x-ui::kbd>⌃</x-ui::kbd>
            </x-ui::kbd.group>
            <x-ui::kbd.group>
                <x-ui::kbd>Ctrl</x-ui::kbd>
                <span>+</span>
                <x-ui::kbd>B</x-ui::kbd>
            </x-ui::kbd.group>
            <x-ui::spinner />
            <x-ui::button disabled size="sm"><x-ui::spinner data-icon="inline-start" /> Loading...</x-ui::button>
            <div class="flex items-center gap-4">
                <x-ui::skeleton class="size-12 rounded-full" />
                <div class="space-y-2">
                    <x-ui::skeleton class="h-4 w-[250px]" />
                    <x-ui::skeleton class="h-4 w-[200px]" />
                </div>
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Empty</h2>
        <x-ui::empty class="border">
            <x-ui::empty.header>
                <x-ui::empty.media variant="icon"><x-ui::icon name="folder" /></x-ui::empty.media>
                <x-ui::empty.title>No Projects Yet</x-ui::empty.title>
                <x-ui::empty.description>You haven't created any projects yet. Get started by creating your first project.</x-ui::empty.description>
            </x-ui::empty.header>
            <x-ui::empty.content>
                <div class="flex gap-2">
                    <x-ui::button>Create Project</x-ui::button>
                    <x-ui::button variant="outline">Import Project</x-ui::button>
                </div>
            </x-ui::empty.content>
        </x-ui::empty>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Item</h2>
        <div class="flex w-full max-w-md flex-col gap-6">
            <x-ui::item variant="outline">
                <x-ui::item.content>
                    <x-ui::item.title>Basic Item</x-ui::item.title>
                    <x-ui::item.description>A simple item with title and description.</x-ui::item.description>
                </x-ui::item.content>
                <x-ui::item.actions>
                    <x-ui::button variant="outline" size="sm">Action</x-ui::button>
                </x-ui::item.actions>
            </x-ui::item>
            <x-ui::item variant="outline" size="sm" href="#">
                <x-ui::item.media><x-ui::icon name="badge-check" class="size-5" /></x-ui::item.media>
                <x-ui::item.content>
                    <x-ui::item.title>Your profile has been verified.</x-ui::item.title>
                </x-ui::item.content>
                <x-ui::item.actions><x-ui::icon name="chevron-right" class="size-4" /></x-ui::item.actions>
            </x-ui::item>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Button Group</h2>
        <div class="flex flex-wrap items-center gap-6">
            <x-ui::button-group>
                <x-ui::button-group>
                    <x-ui::button variant="outline">Archive</x-ui::button>
                    <x-ui::button variant="outline">Report</x-ui::button>
                </x-ui::button-group>
                <x-ui::button-group>
                    <x-ui::button variant="outline">Snooze</x-ui::button>
                </x-ui::button-group>
            </x-ui::button-group>
            <x-ui::button-group>
                <x-ui::button-group.text>https://</x-ui::button-group.text>
                <x-ui::input placeholder="example.com" />
            </x-ui::button-group>
            <x-ui::button-group>
                <x-ui::button variant="secondary" size="sm">Copy</x-ui::button>
                <x-ui::button-group.separator />
                <x-ui::button variant="secondary" size="sm">Paste</x-ui::button>
            </x-ui::button-group>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Input Group</h2>
        <div class="grid w-full max-w-sm gap-6">
            <x-ui::input-group>
                <x-ui::input-group.input placeholder="Search..." />
                <x-ui::input-group.addon><x-ui::icon name="search" /></x-ui::input-group.addon>
                <x-ui::input-group.addon align="inline-end">12 results</x-ui::input-group.addon>
            </x-ui::input-group>
            <x-ui::input-group>
                <x-ui::input-group.addon><x-ui::input-group.text>https://</x-ui::input-group.text></x-ui::input-group.addon>
                <x-ui::input-group.input placeholder="example.com" class="pl-0.5!" />
                <x-ui::input-group.addon align="inline-end"><x-ui::kbd>⌘K</x-ui::kbd></x-ui::input-group.addon>
            </x-ui::input-group>
            <x-ui::input-group>
                <x-ui::input-group.textarea placeholder="Ask, Search or Chat..." />
                <x-ui::input-group.addon align="block-end">
                    <x-ui::input-group.button variant="outline" size="icon-xs" class="rounded-full">+</x-ui::input-group.button>
                    <x-ui::input-group.text class="ml-auto">52% used</x-ui::input-group.text>
                    <x-ui::input-group.button variant="default" size="icon-xs" class="rounded-full">↑</x-ui::input-group.button>
                </x-ui::input-group.addon>
            </x-ui::input-group>

            <form wire:submit="check" class="grid gap-3">
                <x-ui::field>
                    <x-ui::label for="website">Website (Livewire validation)</x-ui::label>
                    <x-ui::input-group>
                        <x-ui::input-group.addon><x-ui::input-group.text>https://</x-ui::input-group.text></x-ui::input-group.addon>
                        <x-ui::input-group.input id="website" wire:model="website" />
                    </x-ui::input-group>
                </x-ui::field>
                <x-ui::button type="submit" class="w-fit">Check</x-ui::button>
            </form>
        </div>
    </section>
</div>
