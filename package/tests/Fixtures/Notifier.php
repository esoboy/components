<?php

namespace Esoboy\Ui\Tests\Fixtures;

use Livewire\Component;

class Notifier extends Component
{
    public function saved(): void
    {
        $this->toast('Saved');
    }

    public function failed(): void
    {
        $this->toast('Failed', description: 'Try again.', type: 'error', duration: 8000);
    }

    public function edit(): void
    {
        $this->dialog('edit')->open();
    }

    public function done(): void
    {
        $this->dialog('edit')->close();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
