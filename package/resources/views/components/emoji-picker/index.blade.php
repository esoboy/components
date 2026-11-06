@blaze
@use('Esoboy\Ui\Support\Model')
@props(['columns' => 9, 'footer' => false, 'value' => null])
@php
$forward = $attributes->except('wire:key')->getAttributes();
foreach (['wire:emoji-select'] as $event) if (isset($forward[$event])) $forward[$event] = '$parent.'.$forward[$event];
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'emoji-picker-'.\Esoboy\Ui\Support\Sequence::next('emoji-picker');
@endphp
{!! \Esoboy\Ui\Support\ViewSlots::mount('ui.emoji-picker', ['columns' => max(1, (int) $columns), 'footer' => $footer, 'value' => Model::value($attributes, $value), ...$forward], 'ui-emoji-picker-'.$key, ['default' => $slot, 'footer' => $footerContent ?? null]) !!}
<x-ui::extras />
