<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Hwkdo\IntranetAppDokumente\Livewire\Concerns\ManagesDocuments;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Review extends Component
{
    use ManagesDocuments;

    public Document $document;

    public string $action = 'extend';

    public function mount(Document $document): void
    {
        $this->authorize('review', $document);
        $this->document = $document;
        $this->detailDocumentId = $document->id;
    }

    public function chooseExtend(): void
    {
        $this->openExtendModal($this->document->id);
    }

    public function chooseUpdate(): void
    {
        $this->openUpdateModal($this->document->id);
    }

    public function chooseDelete(): void
    {
        $this->deleteDocument($this->document->id);
        $this->redirect(route('apps.dokumente.meine-dokumente'), navigate: true);
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.review')
            ->layout('components.layouts.app', [
                'title' => 'Dokument prüfen',
            ]);
    }
}
