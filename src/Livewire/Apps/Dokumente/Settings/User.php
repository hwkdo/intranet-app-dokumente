<?php

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Settings;

use Livewire\Component;

class User extends Component
{
    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.settings.user')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente App - Einstellungen',
            ]);
    }
}
