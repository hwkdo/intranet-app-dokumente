<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Commands;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Illuminate\Console\Command;

class SeedLegacyDocumentAcknowledgmentsCommand extends Command
{
    protected $signature = 'dokumente:seed-legacy-acknowledgments
                            {--dry-run : Nur zählen, nichts speichern}';

    protected $description = 'Markiert Legacy-Import-Dokumente mit Kenntnisnahme für alle App-Nutzer als bereits bestätigt (Import-Bestand)';

    public function handle(DocumentAcknowledgmentService $acknowledgmentService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $documents = Document::query()
            ->whereNotNull('legacy_id')
            ->where('requires_acknowledgment', true)
            ->whereNotNull('current_version_id')
            ->with('currentVersion')
            ->get();

        $this->info(sprintf('Gefundene Legacy-Dokumente mit Kenntnisnahme: %d', $documents->count()));

        if ($dryRun || $documents->isEmpty()) {
            return self::SUCCESS;
        }

        $seeded = 0;
        foreach ($documents as $document) {
            $version = $document->currentVersion;
            if ($version === null) {
                continue;
            }

            $seeded += $acknowledgmentService->seedForAllAppUsers($version);
        }

        $this->info(sprintf('Acknowledgment-Upserts (Zeilen): %d', $seeded));

        return self::SUCCESS;
    }
}
