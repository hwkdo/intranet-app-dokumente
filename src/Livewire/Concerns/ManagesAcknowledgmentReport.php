<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Concerns;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Livewire\Attributes\On;

trait ManagesAcknowledgmentReport
{
    public ?int $acknowledgmentReportDocumentId = null;

    public int $acknowledgmentReportKey = 0;

    public function openAcknowledgmentReport(int $documentId): void
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $this->authorize('viewAcknowledgmentReport', $document);

        if (property_exists($this, 'showDetailModal')) {
            $this->showDetailModal = false;
        }

        $this->acknowledgmentReportKey++;
        $this->acknowledgmentReportDocumentId = $documentId;
    }

    #[On('acknowledgment-report-closed')]
    public function closeAcknowledgmentReport(?int $documentId = null, ?int $reportKey = null): void
    {
        if ($reportKey !== null && $reportKey !== $this->acknowledgmentReportKey) {
            return;
        }

        if ($documentId !== null && $documentId !== $this->acknowledgmentReportDocumentId) {
            return;
        }

        $this->acknowledgmentReportDocumentId = null;
    }
}
