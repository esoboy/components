@blaze

{{-- Without a slot, every command in the order of Tiptap's Simple Editor. --}}
<div {{ $attributes->merge(['data-slot' => 'editor-toolbar', 'role' => 'toolbar', 'aria-label' => __('ui::editor.toolbar')]) }} wire:ignore>
    @if ($slot->isEmpty())
    <x-ui::editor.button command="undo" />
    <x-ui::editor.button command="redo" />
    <x-ui::editor.separator />
    <x-ui::editor.button command="heading-1" />
    <x-ui::editor.button command="heading-2" />
    <x-ui::editor.button command="heading-3" />
    <x-ui::editor.separator />
    <x-ui::editor.button command="bullet-list" />
    <x-ui::editor.button command="ordered-list" />
    <x-ui::editor.button command="blockquote" />
    <x-ui::editor.button command="code-block" />
    <x-ui::editor.separator />
    <x-ui::editor.button command="bold" />
    <x-ui::editor.button command="italic" />
    <x-ui::editor.button command="underline" />
    <x-ui::editor.button command="strike" />
    <x-ui::editor.button command="code" />
    <x-ui::editor.link />
    <x-ui::editor.separator />
    <x-ui::editor.button command="horizontal-rule" />
    @else
    {{ $slot }}
    @endif
</div>
