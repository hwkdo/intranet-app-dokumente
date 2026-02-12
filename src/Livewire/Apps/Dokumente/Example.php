<?php

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Livewire\Component;

class Example extends Component
{
    public array $exampleData = [
        'name' => 'Beispiel Item',
        'description' => 'Dies ist ein Beispiel-Item für die Dokumente App',
        'status' => 'active',
        'created_at' => '',
    ];

    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
        $this->exampleData['created_at'] = now()->format('d.m.Y H:i');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.example')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente App - Beispiel',
            ]);
    }
}
