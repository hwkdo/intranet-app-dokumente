<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus;
use Hwkdo\IntranetAppDokumente\Jobs\SyncDocumentToLightRag;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentLightRagState;

class DocumentLightRagQueue
{
    public function enqueue(Document $document): void
    {
        if ($document->current_version_id === null) {
            return;
        }

        $state = DocumentLightRagState::query()->firstOrNew(['document_id' => $document->id]);
        $replaceDocId = null;
        if ($state->exists
            && (int) $state->document_version_id !== (int) $document->current_version_id
            && is_string($state->lightrag_doc_id)
            && $state->lightrag_doc_id !== '') {
            $replaceDocId = $state->lightrag_doc_id;
        }

        $state->fill([
            'document_version_id' => $document->current_version_id,
            'status' => DocumentLightRagStatus::Pending,
            'error_message' => null,
        ])->save();

        SyncDocumentToLightRag::dispatch($document->id, $replaceDocId)->afterCommit();
    }

    public function enqueueMissing(): int
    {
        $queued = 0;

        Document::query()
            ->whereNotNull('current_version_id')
            ->orderBy('id')
            ->chunkById(200, function ($documents) use (&$queued): void {
                $states = DocumentLightRagState::query()
                    ->whereIn('document_id', $documents->modelKeys())
                    ->get()
                    ->keyBy('document_id');

                foreach ($documents as $document) {
                    $state = $states->get($document->id);
                    if ($this->alreadyQueued($state, (int) $document->current_version_id)) {
                        continue;
                    }

                    $this->enqueue($document);
                    $queued++;
                }
            });

        return $queued;
    }

    private function alreadyQueued(?DocumentLightRagState $state, int $versionId): bool
    {
        if ($state === null || (int) $state->document_version_id !== $versionId) {
            return false;
        }

        return in_array($state->status, [
            DocumentLightRagStatus::Pending,
            DocumentLightRagStatus::Processing,
            DocumentLightRagStatus::Processed,
        ], true);
    }
}
