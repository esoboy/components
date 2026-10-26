<?php

use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|url')]
    public string $website = '';

    public function check(): void
    {
        $this->validate();
    }
};
?>

@php
$icon = fn (string $paths) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$paths.'</svg>';
$circleCheck = $icon('<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>');
$alertCircle = $icon('<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>');
$search = $icon('<path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>');
$folder = $icon('<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>');
$chevronRight = $icon('<path d="m9 18 6-6-6-6"/>');
$badgeCheck = $icon('<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>');
@endphp

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Badge</h2>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui::badge>Badge</x-ui::badge>
            <x-ui::badge variant="secondary">Secondary</x-ui::badge>
            <x-ui::badge variant="destructive">Destructive</x-ui::badge>
            <x-ui::badge variant="outline">Outline</x-ui::badge>
            <x-ui::badge variant="secondary" class="bg-blue-500 text-white dark:bg-blue-600">{!! $badgeCheck !!} Verified</x-ui::badge>
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
                {!! $circleCheck !!}
                <x-ui::alert.title>Payment successful</x-ui::alert.title>
                <x-ui::alert.description>Your payment of $29.99 has been processed. A receipt has been sent to your email address.</x-ui::alert.description>
            </x-ui::alert>
            <x-ui::alert variant="destructive">
                {!! $alertCircle !!}
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
                <x-ui::empty.media variant="icon">{!! $folder !!}</x-ui::empty.media>
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
                <x-ui::item.media>{!! str_replace("<svg ", "<svg class=\"size-5\" ", $badgeCheck) !!}</x-ui::item.media>
                <x-ui::item.content>
                    <x-ui::item.title>Your profile has been verified.</x-ui::item.title>
                </x-ui::item.content>
                <x-ui::item.actions>{!! str_replace("<svg ", "<svg class=\"size-4\" ", $chevronRight) !!}</x-ui::item.actions>
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
                <x-ui::input-group.addon>{!! $search !!}</x-ui::input-group.addon>
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
