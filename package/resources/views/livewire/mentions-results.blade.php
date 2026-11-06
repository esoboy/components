<div data-slot="mentions-results" @if ($empty) data-empty @endif @if ($filter) wire:ui-mentions-query="search($event.detail.prefix, $event.detail.query)" @endif>
    <div data-slot="mentions-list">{!! $content !!}</div>
    <div data-slot="mentions-empty">{{ __('ui::mentions.empty') }}</div>
</div>
