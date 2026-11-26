<?php

use Livewire\Component;

new class extends Component {
    public string $control;
    public mixed $answer = null;
    public bool $switched = false;
    public int $events = 0;
    public array $other = [];
    public array $fields = ['deep' => ['pair' => [2, 8], 'value' => 'b']];
    public array $steps = [8, 2];
    public string $saved = '';
    public string $liveCode = '';
    public array $options = [['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']];

    public function mount(string $control): void
    {
        $this->control = $control;
        $this->answer = match ($control) {
            'tags-input', 'transfer', 'questionnaire', 'columns' => [],
            'tabs', 'combobox' => 'a',
            'mentions' => '@',
            'reject', 'menu' => 'a',
            'controlled-multiple' => ['a', 'b'],
            'deferred-rich' => [],
            'dialog-decision' => false,
            'keys' => ['a','b','c'],
            'validation', 'survey-text', 'frozen', 'layout-source', 'bulk', 'rapid-multiple' => [],
            'editor-contract' => '<p><strong>Hello</strong></p>',
            'collapsible', 'trace', 'tour', 'sidebar' => false,
            default => null,
        };
    }

    public function considerRoles(array $roles): void
    {
        $this->events++;
        $this->other = $roles;
        if ($this->switched) $this->answer = $roles;
    }

    public function save(): void
    {
        $this->saved = json_encode($this->answer, JSON_THROW_ON_ERROR);
    }
    public function updatedAnswer(mixed $value): void
    {
        if ($this->control === 'reject' && $value === 'b') $this->answer = 'a';
        if ($this->control === 'dialog-decision' && ! $this->switched) $this->answer = false;
    }

};
?>
<main>
    <form id="matrix-form" wire:submit="save">
        @switch($control)
            @case('calendar')
                <x-ui::calendar id="matrix-input" name="choice" wire:model.live="answer" initial-month="2027-06-01"/>
                @break
            @case('date-picker')
                <x-ui::date-picker id="matrix-input" name="choice" wire:model.live="answer" initial-month="2027-06-01"/>
                @break
            @case('datetime-picker')
                <x-ui::datetime-picker id="matrix-input" name="choice" wire:model.live="answer" initial-month="2027-06-01"/>
                @break
            @case('time-picker')
                <x-ui::time-picker id="matrix-input" name="choice" wire:model.live="answer" />
                @break
            @case('combobox')
                <x-ui::combobox id="matrix-input" name="choice" wire:model.live="answer" :options="$options"/>
                @break
            @case('cascader')
                <x-ui::cascader id="matrix-input" name="choice" wire:model.live="answer" :nodes="$options"/>
                @break
            @case('tree')
                <x-ui::tree id="matrix-input" name="choice" wire:model.live="answer" :nodes="$options"/>
                @break
            @case('tree-select')
                <x-ui::tree-select id="matrix-input" name="choice" wire:model.live="answer" :nodes="$options"/>
                @break
            @case('command')
                <x-ui::command id="matrix-input" name="choice" wire:model.live="answer" :options="$options"/>
                @break
            @case('transfer')
                <x-ui::transfer id="matrix-input" name="choice" wire:model.live="answer" :options="$options"/>
                @break
            @case('tags-input')
                <x-ui::tags-input id="matrix-input" name="choice" wire:model.live="answer" />
                @break
            @case('mentions')
                <x-ui::mentions id="matrix-input" name="choice" wire:model.live="answer" :options="$options"/>
                @break
            @case('rating')
                <x-ui::rating id="matrix-input" name="choice" wire:model.live="answer" />
                @break
            @case('questionnaire')
                <x-ui::questionnaire id="matrix-input" wire:model.live="answer" :items="[['name' => 'choice', 'prompt' => 'Pick', 'choices' => $options]]"/>
                @break
            @case('tabs')
                <x-ui::tabs wire:model.live="answer">
                    <x-ui::tabs.content value="b">Second panel</x-ui::tabs.content>
                    <x-ui::tabs.list><x-ui::tabs.tab value="a">Alpha</x-ui::tabs.tab><x-ui::tabs.tab value="b">Beta</x-ui::tabs.tab></x-ui::tabs.list>
                    <x-ui::tabs.content value="a">First panel</x-ui::tabs.content>
                </x-ui::tabs>
                @break
            @case('collapsible')
                <x-ui::collapsible wire:model.live="answer">
                    <x-ui::collapsible.trigger><button type="button" id="toggle">Open</button></x-ui::collapsible.trigger>
                    <x-ui::collapsible.content>Details</x-ui::collapsible.content>
                </x-ui::collapsible>
                @break
            @case('trace')
                <x-ui::trace.collapsible wire:model.live="answer">
                    <x-ui::trace.collapsible-trigger><button type="button" id="toggle">Open</button></x-ui::trace.collapsible-trigger>
                    <x-ui::trace.collapsible-content>Details</x-ui::trace.collapsible-content>
                </x-ui::trace.collapsible>
                @break
            @case('tour')
                <x-ui::tour wire:model.live="answer">
                    <x-ui::tour.trigger><button type="button" id="toggle">Open</button></x-ui::tour.trigger>
                    <x-ui::tour.content><x-ui::tour.step target="#toggle" :index="0">Step</x-ui::tour.step></x-ui::tour.content>
                </x-ui::tour>
                @break
            @case('sidebar')
                <x-ui::sidebar wire:model.live="answer">
                    <x-ui::sidebar.content>Sidebar</x-ui::sidebar.content>
                    <x-ui::sidebar.inset><x-ui::sidebar.trigger><button type="button" id="toggle">Open</button></x-ui::sidebar.trigger></x-ui::sidebar.inset>
                </x-ui::sidebar>
                @break
            @case('columns')
                <x-ui::data-table layout-model="answer" :rows="[['id'=>1,'title'=>'One']]" :columns="['id'=>['label'=>'ID'],'title'=>['label'=>'Title']]">
                    <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
                </x-ui::data-table>
                @break
            @case('reject')
                <x-ui::select id="reject" name="choice" wire:model.live="answer" :options="$options" />
                @break
            @case('menu')
                <x-ui::dropdown-menu.radio-group id="dropdown-menu" name="dropdown-menu" wire:model.live="answer">
                    <x-ui::dropdown-menu.radio-item value="a" :close-on-select="false">Alpha</x-ui::dropdown-menu.radio-item>
                    <x-ui::dropdown-menu.radio-item value="b" :close-on-select="false">Beta</x-ui::dropdown-menu.radio-item>
                </x-ui::dropdown-menu.radio-group>
                <x-ui::context-menu.radio-group id="context-menu" name="context-menu" wire:model.live="answer">
                    <x-ui::context-menu.radio-item value="a" :close-on-select="false">Alpha</x-ui::context-menu.radio-item>
                    <x-ui::context-menu.radio-item value="b" :close-on-select="false">Beta</x-ui::context-menu.radio-item>
                </x-ui::context-menu.radio-group>
                <x-ui::menubar.radio-group id="menubar" name="menubar" wire:model.live="answer">
                    <x-ui::menubar.radio-item value="a" :close-on-select="false">Alpha</x-ui::menubar.radio-item>
                    <x-ui::menubar.radio-item value="b" :close-on-select="false">Beta</x-ui::menubar.radio-item>
                </x-ui::menubar.radio-group>
                @break
            @case('survey-text')
            @case('frozen')
                @php($questions = [['name'=>'choice', 'prompt'=>'Pick', 'choices'=>$options, 'input'=>['label'=>'Comment']]])
                @if ($control === 'frozen')
                    <x-ui::questionnaire :value="$answer" :items="$questions" wire:value-changed="$set('events', events + 1)" />
                @else
                    <x-ui::questionnaire wire:model.live="answer" :items="$questions" />
                @endif
                @break
            @case('slider-contract')
                <x-ui::slider id="empty" name="empty" wire:model.live="answer" :min="1" :max="10" />
                <x-ui::slider id="steps" name="steps" wire:model.live="steps" :max="10" />
                <x-ui::slider id="nested" wire:model.live="fields['deep']['pair']" :max="10" />
                <x-ui::select id="bracket" wire:model.live="fields['deep']['value']" :options="$options" />
                @break
            @case('validation')
                @foreach (['calendar','date-picker','datetime-picker','time-picker','combobox','icon-select','tree','tree-select','cascader','transfer','command'] as $kind)
                    <x-dynamic-component :component="'ui::'.$kind" :name="$kind" required />
                @endforeach
                <x-ui::toggle-group name="toggle" required><x-ui::toggle-group.item value="a">A</x-ui::toggle-group.item></x-ui::toggle-group>
                @break
            @case('otp')
                <x-ui::input-otp id="completion" :length="4" wire:model.live.change="answer" />
                <x-ui::input-otp id="realtime" :length="4" wire:model.live="liveCode" />
                <output id="otp-live">{{ $liveCode }}</output>
                @break
            @case('loading')
                <x-ui::radio-group id="rg" wire:loading.attr="disabled" wire:target="save"><x-ui::radio-group.item value="a" /><x-ui::radio-group.item disabled value="b" /></x-ui::radio-group>
                <x-ui::toggle-group id="tg" wire:loading.attr="disabled" wire:target="save"><x-ui::toggle-group.item value="a">A</x-ui::toggle-group.item><x-ui::toggle-group.item value="b" disabled>B</x-ui::toggle-group.item></x-ui::toggle-group>
                @break
            @case('range-validation')
                <x-ui::date-picker id="entry" name="days" wire:model.live="answer" required range />
                @break
            @case('keys')
                <x-ui::tags-input wire:model.live="answer" />
                @break
            @case('listeners')
                <x-ui::number-input id="number" wire:blur="$set('events', events + 1)" wire:model.live="answer" />
                <x-ui::select id="list" wire:model.live="fields.deep.value" wire:change="$set('events', events + 1)" :options="$options" />
                @break
            @case('editor-contract')
                <p id="error">Title error</p>
                <x-ui::editor id="editor" wire:model.live="answer" name="body" :output-type="$switched ? 'markdown' : 'html'" :aria-invalid="$switched ? null : 'true'" :aria-errormessage="$switched ? null : 'error'" />
                @break
            @case('rapid-multiple')
                <x-ui::select multiple id="numeric-options" name="ids" wire:model.live="answer"
                    :options="[['value'=>0,'label'=>'Number'], ['value'=>'0','label'=>'String'], ['value'=>'','label'=>'Empty']]" />
                @break
            @case('deferred-rich')
                <x-ui::select id="rich" multiple wire:model="answer"
                    :options="[['value'=>'','label'=>'Empty'],['value'=>0,'label'=>'Number','description'=>'Details'],['value'=>'0','label'=>'String']]" />
                @break
            @case('controlled-multiple')
                <x-ui::select id="controlled-multiple" name="choice" multiple :value="$answer" :options="$options" wire:value-changed="considerRoles($event.detail.value)" />
                @break
            @case('dialog-decision')
                <x-ui::dialog id="decision-dialog" wire:model.live="answer">
                    <x-ui::dialog.trigger><button type="button">Open decision</button></x-ui::dialog.trigger>
                    <x-ui::dialog.content><x-ui::dialog.title>Decision</x-ui::dialog.title></x-ui::dialog.content>
                </x-ui::dialog>
                <button type="button" wire:click="$set('answer', true)">Owner opens</button>
                @break
            @case('deferred-select')
                <x-ui::select id="native-select" name="role" wire:model="answer" :options="$options" />
                @break
            @case('initial-disclosure')
                <x-ui::collapsible initial-open><x-ui::collapsible.trigger><button type="button">Details</button></x-ui::collapsible.trigger><x-ui::collapsible.content>Details content</x-ui::collapsible.content></x-ui::collapsible>
                <x-ui::trace.collapsible open><x-ui::trace.collapsible-trigger><button type="button">Trace</button></x-ui::trace.collapsible-trigger><x-ui::trace.collapsible-content>Trace content</x-ui::trace.collapsible-content></x-ui::trace.collapsible>
                @break
            @case('bulk')
                <x-ui::data-table id="renderless" wire:model.boolean.renderless.live="answer" :rows="[['id'=>'one'],['id'=>'two']]" :columns="['id'=>['label'=>'ID']]" />
                <x-ui::data-table id="defer" wire:model="other" :rows="[['id'=>'x'],['id'=>'y']]" :columns="['id'=>['label'=>'ID']]" />
                @break
            @case('layout-source')
                <x-ui::data-table id="settings" :layout-model="$switched ? 'other' : 'answer'" :rows="[['id'=>1]]" :columns="['id'=>['label'=>'ID'],'title'=>['label'=>'Title']]">
                    <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
                </x-ui::data-table>
                @break
            @case('page-sizes')
                @foreach ($switched ? ['b','a'] : ['a','b'] as $key)
                    <x-ui::data-table.page-size :wire:key="'size-'.$key" :initial-value="$key" :options="$options" />
                @endforeach
                @break
            @case('dialogs')
                @foreach (['dialog','alert-dialog','drawer','sheet'] as $popup)
                    <x-dynamic-component :component="'ui::'.$popup" :id="$popup">
                        <x-dynamic-component :component="'ui::'.$popup.'.trigger'"><button type="button">Open {{ $popup }}</button></x-dynamic-component>
                        <x-dynamic-component :component="'ui::'.$popup.'.content'">
                            <x-dynamic-component :component="'ui::'.$popup.'.title'">Title</x-dynamic-component>
                            <button type="button" onclick="this.closest('dialog').close()">Close</button>
                        </x-dynamic-component>
                    </x-dynamic-component>
                @endforeach
                <input id="unrelated-input" wire:model.live="saved">
                @break
        @endswitch
        <button type="submit">Save</button>
    </form>
    <output id="matrix-saved">{{ $saved }}</output>
    <button type="button" id="switch" wire:click="$toggle('switched')">Switch</button>
    <output id="switch-state">{{ (int) $switched }}</output>
    <output id="events">{{ $events }}</output>
    <output id="matrix-live">{{ json_encode($answer, JSON_THROW_ON_ERROR) }}</output>
</main>
