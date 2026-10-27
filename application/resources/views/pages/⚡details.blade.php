<?php

use Livewire\Component;

new class extends Component
{
    public int $step = 2;

    public function next(): void
    {
        $this->step = min(4, $this->step + 1);
    }
};
?>

@php
$truck = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>';
$check = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
@endphp

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Description List</h2>
        <x-ui::description-list :columns="3">
            <x-ui::description-list.item><x-ui::description-list.term>Order</x-ui::description-list.term><x-ui::description-list.details>#4189</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Status</x-ui::description-list.term><x-ui::description-list.details><x-ui::badge variant="success">Paid</x-ui::badge></x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Total</x-ui::description-list.term><x-ui::description-list.details>$316.00</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item :span="3"><x-ui::description-list.term>Shipping address</x-ui::description-list.term><x-ui::description-list.details>100 Market St, Suite 300, San Francisco, CA 94105</x-ui::description-list.details></x-ui::description-list.item>
        </x-ui::description-list>

        <x-ui::description-list orientation="horizontal" class="max-w-lg">
            <x-ui::description-list.item><x-ui::description-list.term>Full name</x-ui::description-list.term><x-ui::description-list.details>Margot Foster</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Email</x-ui::description-list.term><x-ui::description-list.details>margot@example.com</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Role</x-ui::description-list.term><x-ui::description-list.details>Backend Developer</x-ui::description-list.details></x-ui::description-list.item>
        </x-ui::description-list>
    </section>

    <section class="space-y-6">
        <h2 class="text-lg font-semibold">Steps</h2>
        <x-ui::steps :current="$step">
            <x-ui::steps.item title="Account" description="Create your account" />
            <x-ui::steps.item title="Shop" description="Set up your store" />
            <x-ui::steps.item title="Payment" description="Connect a bank" />
            <x-ui::steps.item title="Done" />
        </x-ui::steps>
        <x-ui::button variant="outline" size="sm" wire:click="next">Next step</x-ui::button>
        <x-ui::steps :current="2" orientation="vertical" class="max-w-sm">
            <x-ui::steps.item title="Order placed" description="Jan 3, 10:24" />
            <x-ui::steps.item title="Payment failed" description="Card declined" status="error" />
            <x-ui::steps.item title="Shipped" />
        </x-ui::steps>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Timeline</h2>
        <x-ui::timeline class="max-w-md">
            <x-ui::timeline.item variant="success">
                <x-slot:icon>{!! $check !!}</x-slot:icon>
                <x-ui::timeline.header><x-ui::timeline.title>Delivered</x-ui::timeline.title><x-ui::timeline.time>Today 14:02</x-ui::timeline.time></x-ui::timeline.header>
                <x-ui::timeline.description>Signed for by M. Foster.</x-ui::timeline.description>
            </x-ui::timeline.item>
            <x-ui::timeline.item variant="info">
                <x-slot:icon>{!! $truck !!}</x-slot:icon>
                <x-ui::timeline.header><x-ui::timeline.title>Out for delivery</x-ui::timeline.title><x-ui::timeline.time>Today 08:15</x-ui::timeline.time></x-ui::timeline.header>
            </x-ui::timeline.item>
            <x-ui::timeline.item>
                <x-ui::timeline.header><x-ui::timeline.title>Shipped</x-ui::timeline.title><x-ui::timeline.time>Yesterday</x-ui::timeline.time></x-ui::timeline.header>
                <x-ui::timeline.description>Left the Hangzhou warehouse.</x-ui::timeline.description>
            </x-ui::timeline.item>
            <x-ui::timeline.item variant="warning">
                <x-ui::timeline.header><x-ui::timeline.title>Address corrected</x-ui::timeline.title><x-ui::timeline.time>Jan 2</x-ui::timeline.time></x-ui::timeline.header>
            </x-ui::timeline.item>
        </x-ui::timeline>
    </section>
</div>
