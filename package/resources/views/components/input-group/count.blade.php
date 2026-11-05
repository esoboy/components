@blaze

@props([
    'max' => null,
])

@php
$characters = \Esoboy\Ui\Support\PendingControl::characters($max === null ? null : (int) $max);
$max = $characters['max'];
$key = \Esoboy\Ui\Support\Model::scope('input-count', $loop ?? null);
@endphp
{{-- PHP renders the initial count; Alpine updates its presentation as the user types. --}}
<eso-input-group-count {{ $attributes->merge([
    'id' => $key,
    'wire:key' => $key,
    'data-slot' => 'input-group-count',
    'data-max' => $max,
    'data-label' => __('ui::input-group.count'),
    'data-label-max' => __('ui::input-group.count-max'),
    'x-data' => 'uiInputCount',
    'x-effect' => 'render()',
]) }} wire:ignore>{{ $max ? __('ui::input-group.count-max', $characters) : __('ui::input-group.count', $characters) }}</eso-input-group-count>
