<?php

namespace Esoboy\Ui\Livewire;

use Dom\HTMLDocument;
use Esoboy\Ui\Support\SearchText;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Command extends Component
{
    #[Reactive]
    public string $markup = '';

    #[Locked]
    public bool $hasParent = false;

    public string $query = '';

    public function render(): View
    {
        $document = HTMLDocument::createFromString('<!doctype html><html><head><meta charset="utf-8"></head><body>'.$this->markup.'</body></html>', LIBXML_NOERROR);
        $query = trim($this->query);
        $matches = 0;
        foreach ($document->querySelectorAll('[data-slot="command-item"]') as $item) {
            $text = $item->getAttribute('data-value').' '.$item->textContent.' '.$item->getAttribute('data-keywords');
            if ($item->hasAttribute('hidden') || ! SearchText::matches($query, $text)) {
                $item->setAttribute('hidden', '');
            } else {
                $matches++;
            }

            $action = $item->getAttribute('wire:click');
            if ($this->hasParent && $action && ! str_starts_with($action, '$parent.')) {
                $item->setAttribute('wire:click', '$parent.'.$action);
            }
        }
        foreach ($document->querySelectorAll('[data-slot="command-group"]') as $group) {
            if (! $group->querySelector('[data-slot="command-item"]:not([hidden])')) {
                $group->setAttribute('hidden', '');
            }
        }
        if ($query !== '') {
            foreach ($document->querySelectorAll('[data-slot="command-separator"]') as $separator) {
                $separator->setAttribute('hidden', '');
            }
        }
        if ($input = $document->querySelector('[data-command-input]')) {
            $input->setAttribute('wire:model.live.debounce.100ms', 'query');
            $input->setAttribute('value', $this->query);
        }

        $content = '';
        foreach ($document->body->childNodes ?? [] as $child) {
            $content .= $document->saveHtml($child);
        }

        return view('ui::livewire.command', ['content' => $content, 'empty' => $matches === 0]);
    }
}
