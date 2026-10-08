<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Admin;

use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentLightRagState;
use Hwkdo\IntranetAppDokumente\Services\DocumentLightRagQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LightRag extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public function mount(): void
    {
        $this->authorize('manage-app-dokumente');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function enqueueAll(DocumentLightRagQueue $queue): void
    {
        $this->authorize('manage-app-dokumente');

        $queued = $queue->enqueueMissing();

        Flux::toast(
            heading: 'LightRAG',
            text: $queued === 0
                ? 'Alle Dokumente sind schon indexiert oder in Arbeit.'
                : $queued.' Dokumente wurden eingereiht.',
            variant: 'success',
        );
    }

    public function retry(int $documentId, DocumentLightRagQueue $queue): void
    {
        $this->authorize('manage-app-dokumente');

        $document = Document::query()->find($documentId);
        if ($document === null) {
            return;
        }

        $queue->enqueue($document);
    }

    #[Computed]
    public function documents(): LengthAwarePaginator
    {
        return Document::query()
            ->with(['lightRagState', 'currentVersion'])
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->filter === 'failed', fn ($query) => $query->whereHas(
                'lightRagState',
                fn ($state) => $state->where('status', DocumentLightRagStatus::Failed->value),
            ))
            ->when($this->filter === 'working', fn ($query) => $query->whereHas(
                'lightRagState',
                fn ($state) => $state->whereIn('status', [
                    DocumentLightRagStatus::Pending->value,
                    DocumentLightRagStatus::Processing->value,
                ]),
            ))
            ->when($this->filter === 'indexed', fn ($query) => $query->whereHas(
                'lightRagState',
                fn ($state) => $state->where('status', DocumentLightRagStatus::Processed->value),
            ))
            ->when($this->filter === 'missing', fn ($query) => $query->where(function ($query): void {
                $query->whereDoesntHave('lightRagState')
                    ->orWhereHas('lightRagState', function ($state): void {
                        $state->whereColumn(
                            'intranet_app_dokumente_lightrag_states.document_version_id',
                            '!=',
                            'intranet_app_dokumente_documents.current_version_id',
                        );
                    });
            }))
            ->orderBy('title')
            ->paginate(25);
    }

    #[Computed]
    public function hasOpenWork(): bool
    {
        return DocumentLightRagState::query()
            ->whereIn('status', [
                DocumentLightRagStatus::Pending->value,
                DocumentLightRagStatus::Processing->value,
            ])
            ->exists();
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.admin.light-rag');
    }
}
