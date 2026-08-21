<?php

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class User extends Component
{
    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.settings.user')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente App - Einstellungen',
            ]);
    }
}
