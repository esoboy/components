@blaze

@use('Esoboy\Ui\Assets')
@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\EditorUploads')
@use('Esoboy\Ui\Support\EditorControls')
@use('Esoboy\Ui\Support\Model')
@use('Esoboy\Ui\Support\RichText')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Not in shadcn: a Tiptap editor framed as its Textarea. format (text, basic, full, a named format or a list of features) decides all it does: what can be written and what pasted HTML keeps (its whitelist), and the tools it offers; the toolbar fits it unless the slot holds an <x-ui::editor.toolbar>. Ui::editor($name) reaches it by its name, else the property its wire:model binds; a name also keeps a hidden input for forms. The bound HTML is sanitized to the format as it comes in here; the app sanitizes what comes back before storing it, with Ui::sanitize($html, the same format). What it uploads goes to the app's uploader (config ui.editor.uploader), with upload-context, sealed: media picked or dropped, and images on other hosts than the app's (ui.editor.hosts) or pasted as data, when someone saves them or as they come in (ui.editor.auto_upload). --}}
@props([
    'format' => 'full',
    'collapseToolbar' => true,
    'maxLength' => null,
    'uploadContext' => [],
    'placeholder' => null,
    'label' => null,
    'value' => null,
    'disabled' => false,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$rich = RichText::resolve($format);
$value = Model::value($attributes, $value);
$value = $rich->sanitize(is_string($value) ? $value : '');
$name = $attributes->get('name');
$counts = $rich->has('character-count') || $maxLength;
$uploads = EditorUploads::enabled() && $rich->has('image', 'audio', 'video', 'file');
$images = $uploads && $rich->has('image');

$slash = EditorControls::slash($rich);
$emoji = $rich->has('emoji');
$find = $rich->has('find-replace');
$tools = $rich->has('toolbar', 'bubble-menu');
$links = $rich->has('bubble-menu') && $rich->has('link');
$characters = $counts ? RichText::characters($value, $format) : 0;
$key = $attributes->get('wire:key') ?? Model::scope('editor-panels', $loop ?? null);
@endphp


<eso-editor {{ $attributes->whereDoesntStartWith('wire:model')->except('name')->merge([
    'data-slot' => 'editor', 'x-data' => 'uiEngine('.json_encode(Model::binding($attributes)).')', 'data-engine' => app(Assets::class)->url('editor.js'),
    'data-format' => json_encode($rich->client(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    'data-labels' => json_encode(['expand' => __('ui::editor.expand-details'), 'collapse' => __('ui::editor.collapse-details'), 'task' => __('ui::editor.task-checkbox'), 'caption' => __('ui::editor.caption-placeholder'), 'failed' => __('ui::editor.upload-failed'), 'keyboard' => $rich->has('toolbar', 'bubble-menu') ? __('ui::editor.keyboard-hint') : ''], JSON_UNESCAPED_UNICODE),
    'data-upload' => $uploads ? route('esoboy-ui.editor.upload') : false,
    'data-upload-token' => $uploads ? csrf_token() : false,
    'data-upload-context' => $uploads && $uploadContext ? EditorUploads::seal($uploadContext) : false,
    'data-image-hosts' => $images ? implode(' ', EditorUploads::hosts()) : false,
    'data-auto-upload' => $images && EditorUploads::auto() ? implode(' ', EditorUploads::auto()) : false,
    'data-max-length' => $maxLength ?? false,
    'data-placeholder' => $placeholder ?? false,
    'data-label' => $label ?? __('ui::editor.label'),
    'data-name' => $name ?? false,
    'data-editor-name' => $name ?? Model::key($attributes) ?? false,
    'data-disabled' => (bool) $disabled,
    ...$state->attributes(),
]) }}>
    @if (! $slot->isEmpty())
    {{ $slot }}
    @elseif ($rich->has('toolbar'))
    <x-ui::editor.toolbar :collapse="$collapseToolbar" />
    @endif
    @if ($find || $counts || $uploads || $slash || $emoji || $tools)
    @livewire('ui.editor-panels', compact('slash', 'emoji', 'find', 'counts', 'uploads', 'images', 'maxLength', 'characters', 'tools', 'links'), key('ui-'.$key))
    @endif
    <div data-slot="editor-content" wire:ignore>{!! $value !!}</div>
    @if ($rich->has('source'))
    <textarea data-slot="editor-source" aria-label="{{ __('ui::editor.source') }}" spellcheck="false" hidden wire:ignore></textarea>
    @endif
    <x-ui::editor.menus />
    <input type="hidden" data-editor-model @if ($name) name="{{ $name }}" @endif value="{{ $value }}" {{ Model::input($attributes) }}>
</eso-editor>
<x-ui::field.error :state="$state" />
