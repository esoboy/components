<?php

namespace Esoboy\Ui\Livewire;

use Dom\HTMLDocument;
use Esoboy\Ui\Support\SearchText;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class MentionsResults extends Component
{
    #[Reactive]
    public string $markup = '';

    #[Reactive]
    public bool $filter = true;

    #[Reactive]
    public string $fallbackPrefix = '@';

    public ?string $prefix = null;

    public string $query = '';

    public function search(string $prefix, string $query): void
    {
        $this->prefix = $prefix;
        $this->query = $query;
    }

    public function render(): View
    {
        $document = HTMLDocument::createFromString('<!doctype html><html><head><meta charset="utf-8"></head><body>'.$this->markup.'</body></html>', LIBXML_NOERROR);
        $matches = 0;
        foreach ($document->querySelectorAll('[data-slot="mentions-item"]') as $item) {
            $prefix = $item->getAttribute('data-prefix') ?? $this->fallbackPrefix;
            $text = $item->textContent.' '.$item->getAttribute('data-value');
            if ($this->filter && $this->prefix !== null && ($prefix !== $this->prefix || ! SearchText::matches($this->query, $text))) {
                $item->setAttribute('hidden', '');
            }
            if (! $item->hasAttribute('hidden')) {
                $matches++;
            }
        }
        $content = '';
        foreach ($document->body->childNodes ?? [] as $child) {
            $content .= $document->saveHtml($child);
        }

        return view('ui::livewire.mentions-results', ['content' => $content, 'empty' => $matches === 0]);
    }
}
