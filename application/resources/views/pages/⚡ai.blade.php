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
@endphp

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Code Block</h2>
        <x-ui::code-block>
            <x-ui::code-block.header>
                <x-ui::code-block.title><x-ui::icon name="file" />app/Livewire/Counter.php</x-ui::code-block.title>
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
            <x-ui::code-block.header><x-ui::code-block.title><x-ui::icon name="terminal" />php artisan test</x-ui::code-block.title></x-ui::code-block.header>
            <x-ui::code-block.content wrap code="FAILED  Tests\Feature\CheckoutTest > it charges the card&#10;Expected status code 200 but received 500." />
            <x-ui::code-block.footer>Exited with code 1</x-ui::code-block.footer>
        </x-ui::code-block>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Trace</h2>
        <x-ui::trace>
            <x-ui::trace.line>
                <x-ui::trace.icon><x-ui::icon name="search" /></x-ui::trace.icon>
                <x-ui::trace.title>Searched orders for #4189</x-ui::trace.title>
                <x-ui::trace.description>0.4s</x-ui::trace.description>
            </x-ui::trace.line>
            <x-ui::trace.collapsible default-open>
                <x-ui::trace.collapsible-trigger>
                    <x-ui::trace.line><x-ui::trace.icon><x-ui::icon name="file" /></x-ui::trace.icon><x-ui::trace.title>Read 3 files</x-ui::trace.title></x-ui::trace.line>
                </x-ui::trace.collapsible-trigger>
                <x-ui::trace.collapsible-content>
                    <div>app/Models/Order.php</div>
                    <div>app/Models/Refund.php</div>
                    <div>config/payments.php</div>
                </x-ui::trace.collapsible-content>
            </x-ui::trace.collapsible>
            <button type="button"><x-ui::trace.line status="error"><x-ui::trace.icon><x-ui::icon name="circle-x" /></x-ui::trace.icon><x-ui::trace.title>Refund API returned 502</x-ui::trace.title></x-ui::trace.line></button>
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
