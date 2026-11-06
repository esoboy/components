@blaze
@use('Esoboy\Ui\Support\Model')
@props(['items' => [], 'value' => [], 'current' => null, 'shortcuts' => null, 'questionView' => null])
@php
$value = Model::value($attributes, $value);
$forward = $attributes->except('wire:key')->getAttributes();
if (isset($forward['wire:finish'])) $forward['wire:finish'] = '$parent.'.$forward['wire:finish'];
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'questionnaire-'.\Esoboy\Ui\Support\Sequence::next('questionnaire');
@endphp
{!! \Esoboy\Ui\Support\ViewSlots::mount('ui.questionnaire', ['items' => $items, 'value' => $value, 'current' => $current, 'shortcuts' => $shortcuts, 'questionView' => $questionView, ...$forward], 'ui-questionnaire-'.$key, ['default' => $slot, 'actions' => $actions ?? null]) !!}
