@blaze

@use('Esoboy\Ui\Support\EditorControls')
@use('Esoboy\Ui\Support\RichText')

@aware([
    'format' => 'full',
    'tools' => null,
])

{{-- Without a slot, every control the format and tools have, in groups. --}}
@php
$rich = RichText::resolve($format);
@endphp

<div {{ $attributes->merge(['data-slot' => 'editor-toolbar', 'role' => 'toolbar', 'aria-label' => __('ui::editor.toolbar')]) }} wire:ignore>
    @if ($slot->isEmpty())
    @foreach (EditorControls::toolbar($rich, $rich->tools($tools)) as $group)
    @if (! $loop->first)
    <x-ui::editor.separator />
    @endif
    @foreach ($group as $control)
    <x-ui::editor.control :name="$control" />
    @endforeach
    @endforeach
    @else
    {{ $slot }}
    @endif
</div>
