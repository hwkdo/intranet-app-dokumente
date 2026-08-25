<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Info extends Component
{
    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.info')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente - App-Info',
            ]);
    }
}
