<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Enums\DocumentHistoryEvent;
use Hwkdo\IntranetAppDokumente\Enums\DocumentNewsTitleImageMode;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentHistory;
use Hwkdo\IntranetAppDokumente\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentLifecycleService
{
    public function __construct(
        private readonly DocumentNewsService $newsService,
        private readonly DocumentAcknowledgmentService $acknowledgmentService,
        private readonly DocumentNotificationDispatcher $notificationDispatcher,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     description?: ?string,
     *     gueltig_bis?: ?string,
     *     aktiv?: bool,
     *     uploader_id: int,
     *     responsible_id: int,
     *     gvp_id?: ?int,
     *     category_id: int,
     *     requires_acknowledgment?: bool,
     *     is_onboarding_it?: bool,
     *     is_onboarding_perso?: bool,
     *     legacy_id?: ?int
     * }  $attributes
     */
    public function createDocument(
        array $attributes,
        UploadedFile|string $file,
        ?string $originalFileName = null,
        bool $showInNewsSlider = false,
        bool $dispatchNews = true,
        bool $seedAcknowledgments = false,
        DocumentNewsTitleImageMode $newsTitleImageMode = DocumentNewsTitleImageMode::Auto,
        UploadedFile|string|null $customNewsTitleImage = null,
    ): Document {
        return DB::transaction(function () use ($attributes, $file, $originalFileName, $showInNewsSlider, $dispatchNews, $seedAcknowledgments, $newsTitleImageMode, $customNewsTitleImage): Document {
            $document = Document::query()->create([
                'title' => $attributes['title'],
                'description' => $attributes['description'] ?? null,
                'gueltig_bis' => $attributes['gueltig_bis'] ?? null,
                'aktiv' => $attributes['aktiv'] ?? true,
                'uploader_id' => $attributes['uploader_id'],
                'responsible_id' => $attributes['responsible_id'],
                'gvp_id' => $attributes['gvp_id'] ?? null,
                'category_id' => $attributes['category_id'],
                'requires_acknowledgment' => (bool) ($attributes['requires_acknowledgment'] ?? false),
                'is_onboarding_it' => (bool) ($attributes['is_onboarding_it'] ?? false),
                'is_onboarding_perso' => (bool) ($attributes['is_onboarding_perso'] ?? false),
                'legacy_id' => $attributes['legacy_id'] ?? null,
            ]);

            $version = $this->createVersion($document, (int) $attributes['uploader_id'], $file, $originalFileName, 1);

            $document->update(['current_version_id' => $version->id]);

            $this->recordHistory($document, DocumentHistoryEvent::Created, (int) $attributes['uploader_id'], $version, [
                'title' => $document->title,
            ]);

            if ($seedAcknowledgments && $document->requires_acknowledgment) {
                $this->acknowledgmentService->seedForAllAppUsers($version);
            } elseif ($document->requires_acknowledgment && ! $seedAcknowledgments) {
                $this->notificationDispatcher->notifyAcknowledgmentRequired($document->fresh(['currentVersion']));
            }

            if ($dispatchNews) {
                $this->newsService->publishForCreated(
                    document: $document->fresh(),
                    publisherId: (int) $attributes['uploader_id'],
                    showInSlider: $showInNewsSlider,
                    titleImageMode: $newsTitleImageMode,
                    customTitleImage: $customNewsTitleImage,
                );
            }

            return $document->fresh(['currentVersion', 'category', 'uploader', 'responsible']);
        });
    }

    /**
     * @param  array{
     *     title?: string,
     *     description?: ?string,
     *     gueltig_bis?: ?string,
     *     aktiv?: bool,
     *     responsible_id?: int,
     *     gvp_id?: ?int,
     *     category_id?: int,
     *     requires_acknowledgment?: bool,
     *     is_onboarding_it?: bool,
     *     is_onboarding_perso?: bool
     * }  $attributes
     */
    public function updateMetadata(Document $document, array $attributes, int $actorId): Document
    {
        $document->update(array_filter([
            'title' => $attributes['title'] ?? $document->title,
            'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $document->description,
            'gueltig_bis' => array_key_exists('gueltig_bis', $attributes) ? $attributes['gueltig_bis'] : $document->gueltig_bis,
            'aktiv' => $attributes['aktiv'] ?? $document->aktiv,
            'responsible_id' => $attributes['responsible_id'] ?? $document->responsible_id,
            'gvp_id' => array_key_exists('gvp_id', $attributes) ? $attributes['gvp_id'] : $document->gvp_id,
            'category_id' => $attributes['category_id'] ?? $document->category_id,
            'requires_acknowledgment' => array_key_exists('requires_acknowledgment', $attributes)
                ? (bool) $attributes['requires_acknowledgment']
                : $document->requires_acknowledgment,
            'is_onboarding_it' => array_key_exists('is_onboarding_it', $attributes)
                ? (bool) $attributes['is_onboarding_it']
                : $document->is_onboarding_it,
            'is_onboarding_perso' => array_key_exists('is_onboarding_perso', $attributes)
                ? (bool) $attributes['is_onboarding_perso']
                : $document->is_onboarding_perso,
        ], fn ($value) => true));

        return $document->fresh();
    }

    /**
     * @param  array{
     *     title?: string,
     *     description?: ?string,
     *     gueltig_bis?: ?string,
     *     aktiv?: bool,
     *     responsible_id?: int,
     *     gvp_id?: ?int,
     *     category_id?: int,
     *     requires_acknowledgment?: bool
     * }  $attributes
     */
    public function createNewVersion(
        Document $document,
        UploadedFile|string $file,
        int $actorId,
        array $attributes = [],
        ?string $originalFileName = null,
        bool $showInNewsSlider = false,
        bool $dispatchNews = true,
        DocumentNewsTitleImageMode $newsTitleImageMode = DocumentNewsTitleImageMode::Auto,
        UploadedFile|string|null $customNewsTitleImage = null,
        bool $requireReAcknowledgment = true,
    ): Document {
        return DB::transaction(function () use ($document, $file, $actorId, $attributes, $originalFileName, $showInNewsSlider, $dispatchNews, $newsTitleImageMode, $customNewsTitleImage, $requireReAcknowledgment): Document {
            if ($attributes !== []) {
                $this->updateMetadata($document, $attributes, $actorId);
                $document->refresh();
            }

            $nextNumber = (int) $document->versions()->max('version_number') + 1;
            $version = $this->createVersion($document, $actorId, $file, $originalFileName, $nextNumber);

            $document->update([
                'current_version_id' => $version->id,
                'uploader_id' => $actorId,
                'last_review_notified_at' => null,
            ]);

            $this->recordHistory($document, DocumentHistoryEvent::Updated, $actorId, $version, [
                'version_number' => $nextNumber,
                'require_re_acknowledgment' => $requireReAcknowledgment,
            ]);

            if ($document->requires_acknowledgment) {
                if ($requireReAcknowledgment) {
                    $this->notificationDispatcher->notifyAcknowledgmentRequired($document->fresh(['currentVersion']));
                } else {
                    $this->acknowledgmentService->seedForAllAppUsers($version);
                }
            }

            if ($dispatchNews) {
                $this->newsService->publishForUpdated(
                    document: $document->fresh(),
                    publisherId: $actorId,
                    showInSlider: $showInNewsSlider,
                    titleImageMode: $newsTitleImageMode,
                    customTitleImage: $customNewsTitleImage,
                );
            }

            DocumentMatrixService::clearCountMatrixCache();

            return $document->fresh(['currentVersion', 'versions', 'histories']);
        });
    }

    public function extendValidity(Document $document, int $actorId, bool $automatic = true, ?Carbon $manualDate = null): Document
    {
        $old = $document->gueltig_bis?->toDateString();

        if ($automatic) {
            $base = $document->gueltig_bis?->copy() ?? now();
            $newDate = $base->copy()->addYear()->startOfDay();
        } else {
            if ($manualDate === null) {
                throw new \InvalidArgumentException('Manuelles Datum erforderlich.');
            }
            $max = now()->addYears(2)->startOfDay();
            if ($manualDate->gt($max)) {
                throw new \InvalidArgumentException('Manuelle Gültigkeit maximal 2 Jahre in der Zukunft.');
            }
            $newDate = $manualDate->copy()->startOfDay();
        }

        $document->update([
            'gueltig_bis' => $newDate,
            'last_review_notified_at' => null,
        ]);

        $this->recordHistory($document, DocumentHistoryEvent::Extended, $actorId, $document->currentVersion, [
            'old_gueltig_bis' => $old,
            'new_gueltig_bis' => $newDate->toDateString(),
            'automatic' => $automatic,
        ]);

        DocumentMatrixService::clearCountMatrixCache();

        return $document->fresh();
    }

    public function softDelete(Document $document, int $actorId): void
    {
        $this->recordHistory($document, DocumentHistoryEvent::Deleted, $actorId, $document->currentVersion);

        $document->delete();
    }

    protected function createVersion(
        Document $document,
        int $uploaderId,
        UploadedFile|string $file,
        ?string $originalFileName,
        int $versionNumber,
    ): DocumentVersion {
        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $versionNumber,
            'uploader_id' => $uploaderId,
        ]);

        $media = is_string($file)
            ? $version->addMedia($file)
            : $version->addMedia($file->getRealPath());

        if ($originalFileName) {
            $media->usingFileName($originalFileName);
        } elseif ($file instanceof UploadedFile) {
            $media->usingFileName($file->getClientOriginalName());
        }

        $media->toMediaCollection('document');

        return $version;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function recordHistory(
        Document $document,
        DocumentHistoryEvent $event,
        ?int $userId,
        ?DocumentVersion $version = null,
        array $meta = [],
    ): DocumentHistory {
        return DocumentHistory::query()->create([
            'document_id' => $document->id,
            'document_version_id' => $version?->id ?? $document->current_version_id,
            'user_id' => $userId,
            'event' => $event,
            'meta' => $meta,
        ]);
    }
}
