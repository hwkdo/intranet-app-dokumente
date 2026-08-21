<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Hwkdo\IntranetAppDokumente\Enums\AcknowledgmentReportStatusFilter;
use Hwkdo\IntranetAppDokumente\Exports\DocumentAcknowledgmentExport;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AcknowledgmentReport extends Component
{
    use WithPagination;

    public int $documentId;

    public bool $showModal = true;

    public string $statusFilter = 'all';

    public string $userSearch = '';

    public function mount(int $documentId): void
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $this->authorize('viewAcknowledgmentReport', $document);
        $this->documentId = $documentId;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserSearch(): void
    {
        $this->resetPage();
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->dispatch('acknowledgment-report-closed');
    }

    public function updatedShowModal(bool $value): void
    {
        if (! $value) {
            $this->dispatch('acknowledgment-report-closed');
        }
    }

    public function exportExcel(DocumentAcknowledgmentReportService $reportService): BinaryFileResponse
    {
        $document = $this->document;
        $this->authorize('viewAcknowledgmentReport', $document);

        $filter = $this->resolvedStatusFilter();
        $generatedAt = now();
        $rows = $reportService->collect($document, $filter, $this->userSearch);

        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) $document->title) ?: 'dokument';
        $filename = sprintf(
            'kenntnisnahme_%s_v%s_%s.xlsx',
            $safeTitle,
            $document->currentVersion?->version_number ?? '0',
            $generatedAt->format('Y-m-d_His'),
        );

        return Excel::download(
            new DocumentAcknowledgmentExport(
                document: $document,
                rows: $rows,
                generatedAt: $generatedAt,
                statusFilter: $filter,
                search: trim($this->userSearch),
            ),
            $filename,
        );
    }

    #[Computed]
    public function document(): Document
    {
        return Document::query()
            ->withTrashed()
            ->with(['currentVersion'])
            ->findOrFail($this->documentId);
    }

    #[Computed]
    public function counts(): array
    {
        return app(DocumentAcknowledgmentReportService::class)->counts($this->document);
    }

    #[Computed]
    public function rows()
    {
        return app(DocumentAcknowledgmentReportService::class)->paginate(
            document: $this->document,
            status: $this->resolvedStatusFilter(),
            search: $this->userSearch,
            perPage: 25,
        );
    }

    protected function resolvedStatusFilter(): AcknowledgmentReportStatusFilter
    {
        return AcknowledgmentReportStatusFilter::tryFrom($this->statusFilter)
            ?? AcknowledgmentReportStatusFilter::All;
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.acknowledgment-report');
    }
}
