<?php

use Livewire\Component;

new class extends Component
{
    public string $draft = '';

    public array $messages = [];

    public int $earlier = 3;

    public function mount(): void
    {
        $this->messages = [
            ['id' => 'm1', 'role' => 'marker', 'text' => 'Today 09:41'],
            ['id' => 'm2', 'role' => 'agent', 'text' => 'Hi! I am Mia from support. How can I help you today?'],
            ['id' => 'm3', 'role' => 'user', 'text' => 'My order #4189 arrived damaged. The box was crushed and the headphones do not turn on.'],
            ['id' => 'm4', 'role' => 'user', 'text' => 'photo', 'attachment' => ['name' => 'damaged-box.jpg', 'size' => '1.2 MB']],
            ['id' => 'm5', 'role' => 'agent', 'text' => 'I am so sorry about that. I can send a replacement today, or refund the order. Which would you prefer?'],
        ];
    }

    public function send(?string $text = null): void
    {
        $text = trim($text ?? $this->draft);

        if ($text === '') {
            return;
        }

        $this->messages[] = ['id' => 'u'.count($this->messages).uniqid(), 'role' => 'user', 'text' => $text];
        $this->messages[] = ['id' => 'a'.count($this->messages).uniqid(), 'role' => 'agent', 'text' => str_repeat('Thanks, I have started that for you. You will get a confirmation email with the tracking number shortly. ', 3)];
        $this->draft = '';
    }

    public function loadEarlier(): void
    {
        if ($this->earlier === 0) {
            return;
        }

        $older = [];

        foreach (range(1, 4) as $n) {
            $older[] = ['id' => "h{$this->earlier}-{$n}", 'role' => $n % 2 ? 'user' : 'agent', 'text' => "Earlier message {$this->earlier}.{$n} from a previous conversation."];
        }

        $this->messages = [...$older, ...$this->messages];
        $this->earlier--;
    }
};
?>

@php
$icon = fn (string $paths) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$paths.'</svg>';
$image = $icon('<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>');
$file = $icon('<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>');
$x = $icon('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>');
$clock = $icon('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>');
@endphp

<div class="mx-auto flex h-[calc(100dvh-3.5rem)] w-full max-w-2xl flex-col gap-4 p-6">
    <div class="flex items-center gap-3 border-b pb-4">
        <x-ui::avatar><x-ui::avatar.fallback>MS</x-ui::avatar.fallback></x-ui::avatar>
        <div class="text-sm"><div class="font-medium">Mia · Support</div><div class="text-muted-foreground">Usually replies in a few minutes</div></div>
    </div>

    <x-ui::message-scroller.provider auto-scroll>
        <x-ui::message-scroller class="flex-1">
            <x-ui::message-scroller.viewport>
                <x-ui::message-scroller.content class="px-1 pb-4">
                    @if ($earlier > 0)
                    <x-ui::message-scroller.item wire:key="load-earlier">
                        <x-ui::marker variant="separator" type="button" wire:click="loadEarlier" class="cursor-pointer">
                            <x-ui::marker.content>Load earlier messages</x-ui::marker.content>
                        </x-ui::marker>
                    </x-ui::message-scroller.item>
                    @endif

                    @foreach ($messages as $message)
                    <x-ui::message-scroller.item wire:key="{{ $message['id'] }}" :message-id="$message['id']" :scroll-anchor="$message['role'] === 'user'">
                        @if ($message['role'] === 'marker')
                        <x-ui::marker variant="separator">
                            <x-ui::marker.icon>{!! $clock !!}</x-ui::marker.icon>
                            <x-ui::marker.content>{{ $message['text'] }}</x-ui::marker.content>
                        </x-ui::marker>
                        @elseif ($message['role'] === 'agent')
                        <x-ui::message>
                            <x-ui::message.avatar><x-ui::avatar class="size-8"><x-ui::avatar.fallback>MS</x-ui::avatar.fallback></x-ui::avatar></x-ui::message.avatar>
                            <x-ui::message.content>
                                <x-ui::message.header>Mia</x-ui::message.header>
                                <x-ui::bubble variant="muted">
                                    <x-ui::bubble.content>{{ $message['text'] }}</x-ui::bubble.content>
                                </x-ui::bubble>
                            </x-ui::message.content>
                        </x-ui::message>
                        @else
                        <x-ui::message align="end">
                            <x-ui::message.content>
                                @isset ($message['attachment'])
                                <x-ui::attachment>
                                    <x-ui::attachment.media>{!! $image !!}</x-ui::attachment.media>
                                    <x-ui::attachment.content>
                                        <x-ui::attachment.title>{{ $message['attachment']['name'] }}</x-ui::attachment.title>
                                        <x-ui::attachment.description>{{ $message['attachment']['size'] }}</x-ui::attachment.description>
                                    </x-ui::attachment.content>
                                    <x-ui::attachment.trigger href="#" aria-label="Open damaged-box.jpg" />
                                </x-ui::attachment>
                                @else
                                <x-ui::bubble @class(['mb-4' => $loop->index === 2])>
                                    <x-ui::bubble.content>{{ $message['text'] }}</x-ui::bubble.content>
                                    @if ($loop->index === 2)
                                    <x-ui::bubble.reactions>😢</x-ui::bubble.reactions>
                                    @endif
                                </x-ui::bubble>
                                @endisset
                                @if ($loop->last)
                                <x-ui::message.footer>Delivered</x-ui::message.footer>
                                @endif
                            </x-ui::message.content>
                        </x-ui::message>
                        @endif
                    </x-ui::message-scroller.item>
                    @endforeach
                </x-ui::message-scroller.content>
            </x-ui::message-scroller.viewport>
            <x-ui::message-scroller.button />
        </x-ui::message-scroller>
    </x-ui::message-scroller.provider>

    <div class="flex flex-wrap gap-2">
        @foreach (['Send a replacement', 'Refund the order'] as $reply)
        <x-ui::bubble variant="outline">
            <x-ui::bubble.content type="button" wire:click="send('{{ $reply }}')">{{ $reply }}</x-ui::bubble.content>
        </x-ui::bubble>
        @endforeach
    </div>

    <x-ui::attachment.group>
        <x-ui::attachment state="uploading" size="sm">
            <x-ui::attachment.media><x-ui::spinner /></x-ui::attachment.media>
            <x-ui::attachment.content>
                <x-ui::attachment.title>receipt.pdf</x-ui::attachment.title>
                <x-ui::attachment.description>Uploading… 48%</x-ui::attachment.description>
            </x-ui::attachment.content>
            <x-ui::attachment.actions><x-ui::attachment.action aria-label="Cancel">{!! $x !!}</x-ui::attachment.action></x-ui::attachment.actions>
        </x-ui::attachment>
        <x-ui::attachment state="error" size="sm">
            <x-ui::attachment.media>{!! $file !!}</x-ui::attachment.media>
            <x-ui::attachment.content>
                <x-ui::attachment.title>video.mov</x-ui::attachment.title>
                <x-ui::attachment.description>File is too large</x-ui::attachment.description>
            </x-ui::attachment.content>
        </x-ui::attachment>
    </x-ui::attachment.group>

    <form wire:submit="send" class="flex gap-2">
        <x-ui::input wire:model="draft" placeholder="Type a message…" autocomplete="off" />
        <x-ui::button type="submit">Send</x-ui::button>
    </form>
</div>
