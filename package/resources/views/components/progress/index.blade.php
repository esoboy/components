@blaze

@props([
    'value' => null,
    'min' => 0,
    'max' => 100,
])

@php
$percent = $value === null ? null : max(0, min(100, ($value - $min) / max($max - $min, 1) * 100));
@endphp

<div {{ $attributes->merge([
    'role' => 'progressbar',
    'aria-valuemin' => $min,
    'aria-valuemax' => $max,
    'aria-valuenow' => $value ?? false,
    'aria-valuetext' => $percent === null ? false : round($percent).'%',
    'data-slot' => 'progress',
    'data-indeterminate' => $value === null,
    'data-complete' => $percent !== null && $percent >= 100,
]) }}>
    {{ $slot }}
    <div data-slot="progress-track">
        <div data-slot="progress-indicator" @if ($percent !== null) style="inset-inline-start: 0; width: {{ $percent }}%" @endif></div>
    </div>
</div>
