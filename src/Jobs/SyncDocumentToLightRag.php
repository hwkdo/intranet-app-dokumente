<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Jobs;

use Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentLightRagState;
use Hwkdo\IntranetAppDokumente\Models\DocumentVersion;
use Hwkdo\IntranetAppDokumente\Services\LightRagDokumenteClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class SyncDocumentToLightRag implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 180;

    public function __construct(
        public int $documentId,
        public ?string $replaceDocId = null,
    ) {}

    public function handle(LightRagDokumenteClient $client): void
    {
        if (app()->runningUnitTests() && ! config('intranet-app-dokumente.lightrag.execute_in_tests')) {
            return;
        }

        $document = Document::query()->find($this->documentId);
        if ($document === null || $document->trashed() || $document->current_version_id === null) {
            return;
        }

        $version = DocumentVersion::query()->find($document->current_version_id);
        $media = $version?->getFirstMedia('document');
        $path = $media instanceof Media ? $this->readablePath($media) : null;
        if ($version === null || $path === null) {
            $this->markFailed($document->id, $version?->id, 'Die Datei der aktuellen Fassung fehlt.');

            return;
        }

        $state = DocumentLightRagState::query()->firstOrNew(['document_id' => $document->id]);

        if ((int) $state->document_version_id === (int) $version->id
            && $state->status === DocumentLightRagStatus::Processed
            && is_string($state->lightrag_doc_id)
            && $state->lightrag_doc_id !== '') {
            return;
        }

        $state->fill([
            'document_version_id' => $version->id,
            'status' => DocumentLightRagStatus::Processing,
            'error_message' => null,
        ])->save();

        try {
            if (is_string($this->replaceDocId) && $this->replaceDocId !== '') {
                $client->deleteDocument($this->replaceDocId);
                $state->update(['lightrag_doc_id' => null]);
            }

            $extension = $media->extension !== '' ? '.'.$media->extension : '';
            $trackId = $client->uploadFile(
                $path,
                'dokument-'.$document->id.'-v'.$version->version_number.$extension,
            )['track_id'];

            $state->update([
                'track_id' => $trackId,
                'status' => DocumentLightRagStatus::Processing,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            if ($this->attempts() < $this->tries) {
                $this->release(30);

                return;
            }

            report($exception);
            $this->markFailed($document->id, $version->id, $exception->getMessage());
        }
    }

    private function readablePath(Media $media): ?string
    {
        $path = $media->getPath();
        if (is_string($path) && is_file($path)) {
            return $path;
        }

        $relative = $media->getPathRelativeToRoot();
        $disk = Storage::disk($media->disk);
        if ($relative !== '' && $disk->exists($relative)) {
            return $disk->path($relative);
        }

        return null;
    }

    private function markFailed(int $documentId, ?int $versionId, string $message): void
    {
        DocumentLightRagState::query()->updateOrCreate(
            ['document_id' => $documentId],
            [
                'document_version_id' => $versionId,
                'status' => DocumentLightRagStatus::Failed,
                'error_message' => str($message)->limit(2000)->toString(),
            ],
        );
    }
}
