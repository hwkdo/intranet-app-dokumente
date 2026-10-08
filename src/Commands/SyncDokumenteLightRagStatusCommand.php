<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Commands;

use Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus;
use Hwkdo\IntranetAppDokumente\Jobs\SyncDocumentLightRagTrack;
use Hwkdo\IntranetAppDokumente\Models\DocumentLightRagState;
use Hwkdo\IntranetAppDokumente\Services\LightRagDokumenteClient;
use Illuminate\Console\Command;
use Throwable;

class SyncDokumenteLightRagStatusCommand extends Command
{
    protected $signature = 'dokumente:sync-lightrag-status';

    protected $description = 'Schreibt den aktuellen LightRAG-Status der offenen Dokumente in die Datenbank';

    public function handle(LightRagDokumenteClient $client): int
    {
        $written = 0;
        $open = 0;

        DocumentLightRagState::query()
            ->where('status', DocumentLightRagStatus::Processing)
            ->whereNotNull('track_id')
            ->orderBy('id')
            ->each(function (DocumentLightRagState $state) use ($client, &$written, &$open): void {
                try {
                    (new SyncDocumentLightRagTrack($state->document_id))->handle($client);
                } catch (Throwable $exception) {
                    $this->error('Dokument '.$state->document_id.': '.$exception->getMessage());
                    $open++;

                    return;
                }

                $state->refresh();
                if ($state->status === DocumentLightRagStatus::Processing) {
                    $open++;

                    return;
                }

                $written++;
            });

        $this->info($written.' Dokumente aktualisiert, '.$open.' noch in Arbeit.');

        return self::SUCCESS;
    }
}
