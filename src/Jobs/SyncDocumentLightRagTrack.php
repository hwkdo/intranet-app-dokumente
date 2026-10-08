<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Jobs;

use Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus;
use Hwkdo\IntranetAppDokumente\Models\DocumentLightRagState;
use Hwkdo\IntranetAppDokumente\Services\LightRagDokumenteClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncDocumentLightRagTrack implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $documentId) {}

    public function handle(LightRagDokumenteClient $client): void
    {
        if (app()->runningUnitTests() && ! config('intranet-app-dokumente.lightrag.execute_in_tests')) {
            return;
        }

        $state = DocumentLightRagState::query()->where('document_id', $this->documentId)->first();
        if ($state === null || $state->status !== DocumentLightRagStatus::Processing) {
            return;
        }

        $trackId = $state->track_id;
        if (! is_string($trackId) || $trackId === '') {
            return;
        }

        try {
            $track = $client->trackStatus($trackId);
        } catch (Throwable) {
            return;
        }

        if ($track['status'] === 'processed') {
            $state->update([
                'status' => DocumentLightRagStatus::Processed,
                'lightrag_doc_id' => $track['doc_id'],
                'error_message' => null,
                'indexed_at' => now(),
            ]);

            return;
        }

        if ($track['status'] === 'failed') {
            $state->update([
                'status' => DocumentLightRagStatus::Failed,
                'error_message' => str($track['error_message'] ?? 'LightRAG-Verarbeitung fehlgeschlagen.')->limit(2000)->toString(),
            ]);
        }
    }
}
