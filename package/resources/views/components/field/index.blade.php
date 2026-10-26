@blaze

{{-- Not folded: the nesting check below has to see the slot rendered at runtime. --}}
{{-- uiField and showError are read by nested controls through @aware. --}}
@props([
    'orientation' => 'vertical',
    'showError' => true,
    'uiField' => false,
])

@php
// The slot is rendered before this runs, so any field inside it is already in the html.
if (preg_match('/<[a-z][^>]*\sdata-slot="field"[\s\/>]/i', (string) $slot)) {
    throw new \Esoboy\Ui\Exceptions\NestedFieldException;
}
@endphp

<div {{ $attributes->merge(['role' => 'group', 'data-slot' => 'field', 'data-orientation' => $orientation]) }}>{{ $slot }}</div>
