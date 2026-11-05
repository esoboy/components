<?php

use Esoboy\Ui\Facades\Ui;
use Livewire\Component;

new class extends Component
{
    public array $answers = [];

    public ?string $result = null;

    public function finish(array $value): void
    {
        $this->answers = $value;
        $this->result = json_encode($value);
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
            <x-ui::trace.collapsible open>
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
                <x-ui::questionnaire wire:model="answers" wire:finish="finish($event.detail.value)" shortcuts="letters" :items="[
                    ['name' => 'direction', 'title' => 'What should we prototype next?', 'description' => 'Choose a direction or write your own.', 'required' => true, 'input' => true, 'other' => 'other', 'inputLabel' => 'Another answer', 'placeholder' => 'Type another answer…', 'options' => [
                        ['value' => 'delegation', 'label' => 'Delegation', 'class' => 'font-medium', 'description' => 'Show how work moves to a specialist.'],
                        ['value' => 'questions', 'label' => 'Question prompts', 'class' => 'font-medium'],
                        ['value' => 'both', 'label' => 'Both together', 'class' => 'font-medium'],
                    ]],
                    ['name' => 'sources', 'title' => 'What context should the agent inspect?', 'description' => 'Select every source that may affect the implementation.', 'multiple' => true, 'options' => ['files' => 'Relevant source files', 'tests' => 'Existing tests', 'docs' => 'Architecture documentation']],
                    ['name' => 'depth', 'title' => 'How much detail should it include?', 'options' => ['focused' => 'Focused', 'complete' => 'Complete flow']],
                ]" />
            </x-ui::card.content>
        </x-ui::card>
        <p class="text-sm text-muted-foreground">Result: <span id="result">{{ $result ?? '—' }}</span></p>
    </section>
</div>
