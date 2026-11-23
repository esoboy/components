<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Support\DrawingValue;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders simple controls without child snapshots and leaves slot actions on the caller', function (string $name) {
    $html = Blade::render('<x-ui::'.$name.' id="native" wire:model.live="field"><button wire:click="save">Save</button></x-ui::'.$name.'>');
    expect($html)->toContain('wire:model.live="field"', 'wire:click="save"', 'data-ui-control=')
        ->not->toContain('wire:snapshot=', 'wire:id=', 'x-for=', 'x-html=', 'x-text=');
})->with(['number-input', 'slider', 'input-otp', 'editor', 'signature']);

it('applies native number input attributes to the field and keeps the binding on its container', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui::number-input id="quantity" name="units" required placeholder="Units" form="invoice" invalid
            aria-describedby="unit-help" autocomplete="off" wire:model.live="quantity" class="compact" />
        BLADE);
    $root = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('.ui-number-input');
    $input = $root->querySelector('input[type="number"]');
    expect($root->querySelector('.ui-number-input-body')->getAttribute('wire:model.live'))->toBe('quantity')
        ->and($root->classList->contains('compact'))->toBeTrue()
        ->and($input->getAttribute('id'))->toBe('quantity')
        ->and($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($input->hasAttribute('required'))->toBeTrue()
        ->and($root->hasAttribute('required'))->toBeFalse();
    foreach (['name' => 'units', 'placeholder' => 'Units', 'form' => 'invoice', 'aria-describedby' => 'unit-help', 'autocomplete' => 'off'] as $key => $value) {
        expect($input->getAttribute($key))->toBe($value)->and($root->hasAttribute($key))->toBeFalse();
    }
});

it('assigns multipart ids only when there is an explicit or model-derived base', function (string $component, string $props, string $type) {
    $html = Blade::render('<x-ui::'.$component.' '.$props.' name="first" form="settings" aria-describedby="help" />'
        .'<x-ui::'.$component.' '.$props.' name="second" />'
        .'<x-ui::'.$component.' '.$props.' id="named" />');
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $inputs = $document->querySelectorAll('input[type="'.$type.'"]');
    expect(array_map(fn ($input) => $input->getAttribute('id'), iterator_to_array($inputs)))
        ->toBe([null, null, null, null, 'named', 'named-1']);
    foreach ([$inputs->item(0), $inputs->item(1)] as $input) {
        expect($input->getAttribute('form'))->toBe('settings')
            ->and($input->getAttribute('aria-describedby'))->toBe('help')
            ->and($input->getAttribute('aria-label'))->not->toBeEmpty();
    }
    expect($document->querySelector('input[type="hidden"]')->getAttribute('form'))->toBe('settings');

    $bound = Blade::render('<x-ui::'.$component.' wire:model="profile.field" />');
    $field = HTMLDocument::createFromString($bound, LIBXML_NOERROR)->querySelector('input[type="'.$type.'"]');
    expect($field->getAttribute('id'))->toBe('ui-profile-field');
})->with([
    'OTP cells' => ['input-otp', ':length="2"', 'text'],
    'slider thumbs' => ['slider', ':initial-value="[10, 20]"', 'range'],
]);

it('enables pasted image uploads by default and accepts a per-editor opt-out', function () {
    expect(Blade::render('<x-ui::editor id="enabled" />'))->toContain('upload-pasted-images="true"');
    expect(Blade::render('<x-ui::editor id="disabled" :upload-pasted-images="false" />'))->toContain('upload-pasted-images="false"');
    expect(Blade::render('<x-ui::editor id="literal" upload-pasted-images="false" />'))->toContain('upload-pasted-images="false"');
});

it('rejects ambiguous value sources for simple controls', function (string $name) {
    expect(fn () => Blade::render('<x-ui::'.$name.' id="native" wire:model="field" :value="null" />'))->toThrow(ViewException::class);
    expect(fn () => Blade::render('<x-ui::'.$name.' id="native" :value="null" :initial-value="null" />'))->toThrow(ViewException::class);
})->with(['number-input', 'slider', 'input-otp', 'editor', 'signature']);

it('validates native control inputs at the Blade boundary', function (string $template) {
    expect(fn () => Blade::render($template))->toThrow(ViewException::class);
})->with([
    '<x-ui::number-input id="invalid" :step="0" />',
    '<x-ui::number-input id="invalid" :value="[]" />',
    '<x-ui::number-input id="invalid" :min="5" :max="1" />',
    '<x-ui::slider id="invalid" :value="[]" />',
    '<x-ui::slider id="invalid" :value="[null]" />',
    '<x-ui::slider id="invalid" :value="[1, 101]" />',
    '<x-ui::slider id="invalid" orientation="diagonal" />',
    '<x-ui::input-otp id="invalid" :length="13" />',
    '<x-ui::input-otp id="invalid" value="abc" />',
    '<x-ui::input-otp id="invalid" :length="4" :separator-after="4" />',
    '<x-ui::editor id="invalid" :value="[]" />',
    '<x-ui::editor id="invalid" output-type="json" />',
    '<x-ui::cropper id="invalid" src="/image.jpg" :value="[\'x\' => INF, \'y\' => 0, \'width\' => .5, \'height\' => .5]" />',
    '<x-ui::cropper id="invalid" src="/image.jpg" :value="[]" />',
    '<x-ui::signature id="invalid" :value="[[[\'x\' => 2, \'y\' => .2]]]" />',
    '<x-ui::signature id="invalid" :value="[[]]" />',
    '<x-ui::signature id="invalid" :value="array_fill(0, 201, [[\'x\' => .2, \'y\' => .2]])" />',
    '<x-ui::signature id="invalid" :value="[array_fill(0, 4097, [\'x\' => .2, \'y\' => .2])]" />',
]);

it('normalizes crop coordinates at the render boundary', function () {
    expect(DrawingValue::crop(['x' => .9, 'y' => .9, 'width' => .5, 'height' => .5]))
        ->toBe(['x' => .5, 'y' => .5, 'width' => .5, 'height' => .5]);
    expect(Blade::render('<x-ui::cropper id="crop" src="/image.jpg" wire:model="crop" />'))->not->toContain('wire:snapshot=');
});
