<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Exports;

use Carbon\CarbonInterface;
use Hwkdo\IntranetAppDokumente\Enums\AcknowledgmentReportStatusFilter;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class DocumentAcknowledgmentExport implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @param  Collection<int, object>  $rows
     */
    public function __construct(
        private readonly Document $document,
        private readonly Collection $rows,
        private readonly CarbonInterface $generatedAt,
        private readonly AcknowledgmentReportStatusFilter $statusFilter,
        private readonly string $search = '',
    ) {}

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        $generatedAtFormatted = $this->generatedAt
            ->timezone(config('app.timezone'))
            ->format('d.m.Y H:i:s');

        $data = [
            ['Kenntnisnahme-Auswertung'],
            ['Dokument', (string) $this->document->title],
            ['Version', (string) ($this->document->currentVersion?->version_number ?? '—')],
            ['Report erzeugt am', $generatedAtFormatted],
            ['Filter', $this->statusFilter->label()],
            ['Suche', $this->search !== '' ? $this->search : '—'],
            [],
            ['Nachname', 'Vorname', 'E-Mail', 'Status', 'Kenntnisnahme am', 'Methode'],
        ];

        foreach ($this->rows as $row) {
            $acknowledgedAt = $row->acknowledged_at;
            if ($acknowledgedAt instanceof CarbonInterface) {
                $acknowledgedAt = $acknowledgedAt->timezone(config('app.timezone'))->format('d.m.Y H:i:s');
            } elseif (is_string($acknowledgedAt) && $acknowledgedAt !== '') {
                $acknowledgedAt = $acknowledgedAt;
            } else {
                $acknowledgedAt = '—';
            }

            $data[] = [
                (string) $row->nachname,
                (string) $row->vorname,
                (string) $row->email,
                (string) $row->status_label,
                $acknowledgedAt,
                (string) ($row->confirmation_method ?? '—'),
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return 'Kenntnisnahme';
    }
}
