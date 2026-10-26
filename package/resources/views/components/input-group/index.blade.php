@blaze

@php
// The input inside rendered first and left its validation state behind.
$control = \Esoboy\Ui\Support\PendingControl::take();
@endphp

<div {{ $attributes->merge(['role' => 'group', 'data-slot' => 'input-group']) }}>{{ $slot }}</div>
@if ($control?->showsError())
<x-ui::field.error :id="$control->errorId" :messages="$control->messages" />
@endif
