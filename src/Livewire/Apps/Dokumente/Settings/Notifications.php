<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Notifications extends Component
{
    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.settings.notifications')
            ->layout('components.layouts.app', [
                'title' => 'Benachrichtigungen - Dokumente',
            ]);
    }
}
