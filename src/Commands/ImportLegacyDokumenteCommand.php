<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Commands;

use App\Services\IntranetLegacyService;
use Hwkdo\IntranetAppDokumente\Services\LegacyDocumentImportService;
use Illuminate\Console\Command;

class ImportLegacyDokumenteCommand extends Command
{
    protected $signature = 'dokumente:import-legacy {--dry-run : Nur zählen, nichts speichern}';

    protected $description = 'Importiert Dokumente (objekttyp_id=2) aus dem Legacy-Intranet';

    public function handle(LegacyDocumentImportService $importService, IntranetLegacyService $legacyService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $report = $importService->import($legacyService, $dryRun);

        $this->table(
            ['Metric', 'Count'],
            collect($report)->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        return self::SUCCESS;
    }
}
