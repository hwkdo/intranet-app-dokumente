<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Livewire\Component;

class Show extends Component
{
    public function mount(Document $document): void
    {
        $this->authorize('view', $document);

        $this->redirectRoute('apps.dokumente.index', [
            'document' => $document->id,
        ], navigate: true);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
