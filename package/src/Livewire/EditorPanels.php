<?php

namespace Esoboy\Ui\Livewire;

use Esoboy\Ui\Support\Emoji;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EditorPanels extends Component
{
    /** @var array<string, array{string, string, array<string, string>}> */
    #[Locked]
    public array $slash = [];

    #[Locked]
    public bool $emoji = false;

    #[Locked]
    public bool $counts = false;

    #[Locked]
    public ?int $maxLength = null;

    #[Locked]
    public bool $uploads = false;

    #[Locked]
    public bool $images = false;

    #[Locked]
    public bool $find = false;

    #[Locked]
    public bool $tools = false;

    #[Locked]
    public string $tip = '';

    #[Locked]
    public string $shortcut = '';

    #[Locked]
    public string $query = '';

    #[Locked]
    public string $menu = '';

    /** @var array<string, mixed> */
    #[Locked]
    public array $status = [];

    public function mount(int $characters = 0): void
    {
        $this->status = ['characters' => max(0, $characters)];
    }

    public function search(string $menu, string $query): void
    {
        $this->menu = in_array($menu, ['slash', 'emoji'], true) ? $menu : '';
        $this->query = mb_strtolower(mb_substr($query, 0, 80));
    }

    public function showTip(string $label, string $shortcut): void
    {
        $this->tip = mb_substr($label, 0, 150);
        $this->shortcut = mb_substr($shortcut, 0, 60);
    }

    /** @param array<string, mixed> $status */
    public function syncStatus(array $status): void
    {
        $this->status = [
            'characters' => max(0, (int) ($status['characters'] ?? 0)),
            'uploads' => max(0, (int) ($status['uploads'] ?? 0)),
            'refused' => ($status['refused'] ?? false) === true,
            'unsaved' => max(0, (int) ($status['unsaved'] ?? 0)),
            'saving' => ($status['saving'] ?? false) === true,
            'matches' => max(0, (int) ($status['matches'] ?? 0)),
            'match' => max(0, (int) ($status['match'] ?? 0)),
            'searching' => ($status['searching'] ?? false) === true,
            'mathError' => $this->shortText($status['mathError'] ?? null),
            'imageError' => $this->shortText($status['imageError'] ?? null),
            'uploadErrors' => array_values(array_filter(array_map(
                $this->shortText(...),
                is_array($status['uploadErrors'] ?? null) ? array_slice($status['uploadErrors'], -8) : [],
            ))),
        ];
    }

    private function shortText(mixed $message): string
    {
        return is_string($message) ? mb_substr($message, 0, 400) : '';
    }

    public function render(): View
    {
        $slashItems = $this->menu === 'slash'
            ? array_filter($this->slash, fn (array $item, string $key) => str_contains(mb_strtolower($item[1].' '.$key), $this->query), ARRAY_FILTER_USE_BOTH)
            : [];
        $emojiItems = [];
        if ($this->menu === 'emoji' && $this->emoji && $this->query !== '') {
            foreach (Emoji::catalog() as $category) {
                foreach ($category as [$emoji, $label, $keywords]) {
                    $score = Emoji::score($label, $keywords, $this->query);
                    if ($score > 0) {
                        $emojiItems[] = [$emoji, $label, $score];
                    }
                }
            }
            usort($emojiItems, fn (array $a, array $b) => $b[2] <=> $a[2]);
            $emojiItems = array_slice($emojiItems, 0, 8);
        }

        return view('ui::livewire.editor-panels', compact('slashItems', 'emojiItems'));
    }
}
