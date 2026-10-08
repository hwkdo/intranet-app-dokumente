<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Jobs;

use Hwkdo\IntranetAppDokumente\Models\DocumentLightRagState;
use Hwkdo\IntranetAppDokumente\Services\LightRagDokumenteClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RemoveDocumentFromLightRag implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $documentId) {}

    public function handle(LightRagDokumenteClient $client): void
    {
        if (app()->runningUnitTests() && ! config('intranet-app-dokumente.lightrag.execute_in_tests')) {
            return;
        }

        $state = DocumentLightRagState::query()->where('document_id', $this->documentId)->first();
        if ($state === null) {
            return;
        }

        $docId = $state->lightrag_doc_id;
        if (! is_string($docId) || $docId === '') {
            $state->delete();

            return;
        }

        try {
            $client->deleteDocument($docId);
            $state->delete();
        } catch (Throwable $exception) {
            if ($this->attempts() < $this->tries) {
                $this->release(30);

                return;
            }

            report($exception);
            $state->update([
                'error_message' => str($exception->getMessage())->limit(2000)->toString(),
            ]);
        }
    }
}
