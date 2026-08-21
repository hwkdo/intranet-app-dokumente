<?php

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function mount(Document $document): void
    {
        $this->authorize('view', $document);

        $ack = app(DocumentAcknowledgmentService::class);

        if (! $document->requires_acknowledgment || ! $document->currentVersion) {
            session()->flash('message', 'Keine Kenntnisnahme erforderlich.');
            $this->redirect(route('apps.dokumente.index', ['document' => $document->id]), navigate: true);

            return;
        }

        if ($ack->hasAcknowledged($document->currentVersion, (int) auth()->id())) {
            session()->flash('message', 'Bereits zur Kenntnis genommen.');
            $this->redirect(route('apps.dokumente.index', ['document' => $document->id]), navigate: true);

            return;
        }

        if (! $ack->hasDownloadedForAcknowledgment((int) $document->id)) {
            session()->flash('message', 'Bitte laden Sie das Dokument zuerst herunter, bevor Sie die Kenntnisnahme bestätigen.');
            $this->redirect(route('apps.dokumente.index', ['document' => $document->id]), navigate: true);

            return;
        }

        $ack->acknowledge(
            $document->currentVersion,
            (int) auth()->id(),
            DocumentAcknowledgmentService::METHOD_PASSWORD,
        );
        $ack->clearDownloadedForAcknowledgment((int) $document->id);

        session()->flash('message', 'Kenntnisnahme per Passwort bestätigt.');
        $this->redirect(route('apps.dokumente.index', ['document' => $document->id]), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center justify-center py-12">
        <svg class="h-8 w-8 animate-spin text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</div>
