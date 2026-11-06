@blaze

@props([
    'filter' => true,
])

@if ($filter)
    @php
        $owner = \Esoboy\Ui\Support\Model::component();
        $key = $attributes->get('wire:key') ?? $attributes->get('id') ?? 'command-'.\Esoboy\Ui\Support\Sequence::next('command');
    @endphp
    @livewire('ui.command', ['markup' => (string) $slot, 'hasParent' => $owner !== null, ...$attributes->except('wire:key')->getAttributes()], key('ui-command-'.$key))
@else
    <eso-command {{ $attributes->merge(['data-slot' => 'command', 'data-filter' => 'false', 'data-empty' => ! str_contains((string) $slot, 'data-slot="command-item"'), 'x-data' => 'uiCommand', 'x-on:keydown' => 'key($event)', 'x-on:pointermove' => 'hover($event)']) }}>{{ $slot }}</eso-command>
@endif
