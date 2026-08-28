<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\User;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentAcknowledgment;
use Hwkdo\IntranetAppDokumente\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;

class DocumentAcknowledgmentService
{
    public const METHOD_PASSWORD = 'password';

    public static function downloadSessionKey(int $documentId): string
    {
        return 'dokumente.downloaded_for_ack.'.$documentId;
    }

    public function markDownloadedForAcknowledgment(Document $document, ?DocumentVersion $version = null): void
    {
        $target = $version ?? $document->currentVersion;
        if ($target === null || (int) $document->current_version_id !== (int) $target->id) {
            return;
        }

        session([self::downloadSessionKey((int) $document->id) => true]);
    }

    public function hasDownloadedForAcknowledgment(int $documentId): bool
    {
        return (bool) session(self::downloadSessionKey($documentId));
    }

    public function clearDownloadedForAcknowledgment(int $documentId): void
    {
        session()->forget(self::downloadSessionKey($documentId));
    }

    public function acknowledge(DocumentVersion $version, int $userId, string $method = self::METHOD_PASSWORD): DocumentAcknowledgment
    {
        return DocumentAcknowledgment::query()->updateOrCreate(
            [
                'document_version_id' => $version->id,
                'user_id' => $userId,
            ],
            [
                'acknowledged_at' => now(),
                'confirmation_method' => $method,
            ],
        );
    }

    public function hasAcknowledged(DocumentVersion $version, int $userId): bool
    {
        return DocumentAcknowledgment::query()
            ->where('document_version_id', $version->id)
            ->where('user_id', $userId)
            ->exists();
    }

    public function needsAcknowledgment(Document $document, int $userId): bool
    {
        if (! $document->requires_acknowledgment || $document->trashed() || ! $document->currentVersion) {
            return false;
        }

        if ($document->isLegacyImportAcknowledgmentExempt()) {
            return false;
        }

        return ! $this->hasAcknowledged($document->currentVersion, $userId);
    }

    public function seedForAllAppUsers(DocumentVersion $version): int
    {
        $userClass = config('intranet-app-dokumente.user_model', User::class);
        $userIds = $userClass::permission('see-app-dokumente')->pluck('id')->all();

        if ($userIds === []) {
            return 0;
        }

        $now = now();
        $rows = [];
        foreach ($userIds as $userId) {
            $rows[] = [
                'document_version_id' => $version->id,
                'user_id' => $userId,
                'acknowledged_at' => $now,
                'confirmation_method' => 'import',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('intranet_app_dokumente_document_acknowledgments')->upsert(
                $chunk,
                ['user_id', 'document_version_id'],
                ['acknowledged_at', 'confirmation_method', 'updated_at'],
            );
        }

        return count($rows);
    }
}
