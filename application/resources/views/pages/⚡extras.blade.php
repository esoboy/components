<?php

use Esoboy\Ui\Facades\Ui;
use Livewire\Component;

new class extends Component
{
    public ?string $direction = null;

    public string $other = '';

    public array $sources = [];

    public ?string $depth = null;

    public ?string $result = null;

    public function finish(): void
    {
        $this->result = json_encode(['direction' => $this->direction, 'other' => $this->other, 'sources' => $this->sources, 'depth' => $this->depth]);
        Ui::toast('Thanks for the answers', description: $this->result, type: 'success');
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
$icon = fn (string $paths) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$paths.'</svg>';
$check = $icon('<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>');
$alert = $icon('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>');
$info = $icon('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>');
$x = $icon('<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>');
$search = $icon('<path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>');
$file = $icon('<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>');
$terminal = $icon('<path d="M12 19h8"/><path d="m4 17 6-6-6-6"/>');
@endphp

<div class="mx-auto max-w-3xl space-y-12 p-10">
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
            <x-ui::alert variant="success">{!! $check !!}<x-ui::alert.title>Payment received</x-ui::alert.title><x-ui::alert.description>Order #4189 has been paid and will ship today.</x-ui::alert.description></x-ui::alert>
            <x-ui::alert variant="warning">{!! $alert !!}<x-ui::alert.title>Low stock</x-ui::alert.title><x-ui::alert.description>Only 3 units of Studio Headphones are left.</x-ui::alert.description></x-ui::alert>
            <x-ui::alert variant="info">{!! $info !!}<x-ui::alert.title>Maintenance</x-ui::alert.title><x-ui::alert.description>The dashboard will be read-only on Sunday from 2:00 to 3:00.</x-ui::alert.description></x-ui::alert>
            <x-ui::alert variant="destructive">{!! $x !!}<x-ui::alert.title>Payment failed</x-ui::alert.title><x-ui::alert.description>The card was declined. Ask the customer for another method.</x-ui::alert.description></x-ui::alert>
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
        <h2 class="text-lg font-semibold">Code Block</h2>
        <x-ui::code-block>
            <x-ui::code-block.header>
                <x-ui::code-block.title>{!! $file !!}app/Livewire/Counter.php</x-ui::code-block.title>
                <x-ui::code-block.actions><x-ui::code-block.copy-button /></x-ui::code-block.actions>
            </x-ui::code-block.header>
            <x-ui::code-block.content language="php" line-numbers :code="$code" />
        </x-ui::code-block>

        <x-ui::code-block size="sm">
            <x-ui::code-block.content language="diff" line-numbers>
                <x-ui::code-block.line>public function increment(): void</x-ui::code-block.line>
                <x-ui::code-block.line>{</x-ui::code-block.line>
                <x-ui::code-block.line variant="removed">    $this->count = $this->count + 1;</x-ui::code-block.line>
                <x-ui::code-block.line variant="added">    $this->count++;</x-ui::code-block.line>
                <x-ui::code-block.line>}</x-ui::code-block.line>
            </x-ui::code-block.content>
            <x-ui::code-block.actions><x-ui::code-block.copy-button /></x-ui::code-block.actions>
        </x-ui::code-block>

        <x-ui::code-block variant="destructive">
            <x-ui::code-block.header><x-ui::code-block.title>{!! $terminal !!}php artisan test</x-ui::code-block.title></x-ui::code-block.header>
            <x-ui::code-block.content wrap code="FAILED  Tests\Feature\CheckoutTest > it charges the card&#10;Expected status code 200 but received 500." />
            <x-ui::code-block.footer>Exited with code 1</x-ui::code-block.footer>
        </x-ui::code-block>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Trace</h2>
        <x-ui::trace>
            <x-ui::trace.line>
                <x-ui::trace.icon>{!! $search !!}</x-ui::trace.icon>
                <x-ui::trace.title>Searched orders for #4189</x-ui::trace.title>
                <x-ui::trace.description>0.4s</x-ui::trace.description>
            </x-ui::trace.line>
            <x-ui::trace.collapsible default-open>
                <x-ui::trace.collapsible-trigger>
                    <x-ui::trace.line><x-ui::trace.icon>{!! $file !!}</x-ui::trace.icon><x-ui::trace.title>Read 3 files</x-ui::trace.title></x-ui::trace.line>
                </x-ui::trace.collapsible-trigger>
                <x-ui::trace.collapsible-content>
                    <div>app/Models/Order.php</div>
                    <div>app/Models/Refund.php</div>
                    <div>config/payments.php</div>
                </x-ui::trace.collapsible-content>
            </x-ui::trace.collapsible>
            <button type="button"><x-ui::trace.line status="error"><x-ui::trace.icon>{!! $x !!}</x-ui::trace.icon><x-ui::trace.title>Refund API returned 502</x-ui::trace.title></x-ui::trace.line></button>
            <x-ui::trace.line status="running"><x-ui::trace.title>Retrying the refund</x-ui::trace.title></x-ui::trace.line>
            <x-ui::trace.line status="pending"><x-ui::trace.title>Email the customer</x-ui::trace.title></x-ui::trace.line>
        </x-ui::trace>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Questionnaire</h2>
        <x-ui::card class="max-w-md">
            <x-ui::card.content>
                <x-ui::questionnaire wire:submit="finish" :items="[['name' => 'direction', 'required' => true], 'sources', 'depth']" shortcuts="letters">
                    <x-ui::questionnaire.progress />
                    <x-ui::questionnaire.item name="direction" required>
                        <x-ui::questionnaire.title>What should we prototype next?</x-ui::questionnaire.title>
                        <x-ui::questionnaire.description>Choose a direction or write your own.</x-ui::questionnaire.description>
                        <x-ui::questionnaire.choices wire:model="direction">
                            <x-ui::questionnaire.choice value="delegation">
                                <span class="font-medium">Delegation</span>
                                <x-ui::questionnaire.choice-description>Show how work moves to a specialist.</x-ui::questionnaire.choice-description>
                            </x-ui::questionnaire.choice>
                            <x-ui::questionnaire.choice value="questions"><span class="font-medium">Question prompts</span></x-ui::questionnaire.choice>
                            <x-ui::questionnaire.choice value="both"><span class="font-medium">Both together</span></x-ui::questionnaire.choice>
                            <x-ui::questionnaire.input wire:model="other" aria-label="Another answer" placeholder="Type another answer…" />
                        </x-ui::questionnaire.choices>
                        <x-ui::questionnaire.error />
                    </x-ui::questionnaire.item>
                    <x-ui::questionnaire.item name="sources" multiple>
                        <x-ui::questionnaire.title>What context should the agent inspect?</x-ui::questionnaire.title>
                        <x-ui::questionnaire.description>Select every source that may affect the implementation.</x-ui::questionnaire.description>
                        <x-ui::questionnaire.choices wire:model="sources">
                            <x-ui::questionnaire.choice value="files">Relevant source files</x-ui::questionnaire.choice>
                            <x-ui::questionnaire.choice value="tests">Existing tests</x-ui::questionnaire.choice>
                            <x-ui::questionnaire.choice value="docs">Architecture documentation</x-ui::questionnaire.choice>
                        </x-ui::questionnaire.choices>
                        <x-ui::questionnaire.error />
                    </x-ui::questionnaire.item>
                    <x-ui::questionnaire.item name="depth">
                        <x-ui::questionnaire.title>How much detail should it include?</x-ui::questionnaire.title>
                        <x-ui::questionnaire.choices wire:model="depth">
                            <x-ui::questionnaire.choice value="focused">Focused</x-ui::questionnaire.choice>
                            <x-ui::questionnaire.choice value="complete">Complete flow</x-ui::questionnaire.choice>
                        </x-ui::questionnaire.choices>
                        <x-ui::questionnaire.error />
                    </x-ui::questionnaire.item>
                    <x-ui::questionnaire.actions>
                        <x-ui::questionnaire.previous />
                        <x-ui::questionnaire.skip />
                        <x-ui::questionnaire.next />
                        <x-ui::questionnaire.submit />
                    </x-ui::questionnaire.actions>
                </x-ui::questionnaire>
            </x-ui::card.content>
        </x-ui::card>
        <p class="text-sm text-muted-foreground">Result: <span id="result">{{ $result ?? '—' }}</span></p>
    </section>
</div>
