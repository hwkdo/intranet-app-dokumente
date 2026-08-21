<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\Gvp;
use App\Models\User;
use App\Services\IntranetLegacyService;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LegacyDocumentImportService
{
    public function __construct(
        private readonly DocumentLifecycleService $lifecycle,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, missing_file: int, unmapped: int}
     */
    public function import(IntranetLegacyService $legacyService, bool $dryRun = false): array
    {
        $report = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'missing_file' => 0,
            'unmapped' => 0,
        ];

        $legacyDocuments = $legacyService->getDokumenteExport();
        $categoryMap = DocumentCategory::query()->pluck('id', 'name');
        $sonstigesId = (int) ($categoryMap['Sonstiges'] ?? DocumentCategory::query()->value('id'));

        foreach ($legacyDocuments as $legacy) {
            $legacyId = (int) ($legacy['id'] ?? 0);
            if ($legacyId < 1) {
                $report['skipped']++;

                continue;
            }

            $title = trim((string) ($legacy['titel'] ?? ''));
            if ($title === '') {
                $report['skipped']++;

                continue;
            }

            $categoryName = trim((string) ($legacy['matrixkategorie_name'] ?? ''));
            $categoryId = (int) ($categoryMap[$categoryName] ?? $sonstigesId);
            if ($categoryName !== '' && ! $categoryMap->has($categoryName)) {
                $report['unmapped']++;
            }

            $uploaderId = User::query()->where('legacy_id', (int) ($legacy['uploader_id'] ?? 0))->value('id');
            $responsibleId = User::query()->where('legacy_id', (int) ($legacy['owner_id'] ?? 0))->value('id');
            $gvpId = Gvp::query()->where('legacy_id', (int) ($legacy['gvp_id'] ?? 0))->value('id');

            if (! $uploaderId) {
                $uploaderId = $responsibleId;
            }
            if (! $responsibleId) {
                $responsibleId = $uploaderId;
            }
            if (! $uploaderId || ! $responsibleId) {
                $report['unmapped']++;
                $report['skipped']++;

                continue;
            }

            $existing = Document::query()->withTrashed()->where('legacy_id', $legacyId)->first();
            $requiresAck = (bool) ($legacy['forceable'] ?? false);

            $attributes = [
                'title' => $title,
                'description' => $legacy['beschreibung'] ?? null,
                'gueltig_bis' => $legacy['gueltig_bis'] ?? null,
                'aktiv' => (bool) ($legacy['aktiv'] ?? true),
                'uploader_id' => (int) $uploaderId,
                'responsible_id' => (int) $responsibleId,
                'gvp_id' => $gvpId ? (int) $gvpId : null,
                'category_id' => $categoryId,
                'requires_acknowledgment' => $requiresAck,
                'is_onboarding_it' => (bool) ($legacy['is_onboarding_it'] ?? false),
                'is_onboarding_perso' => (bool) ($legacy['is_onboarding_perso'] ?? false),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existing ? $report['updated']++ : $report['created']++;

                continue;
            }

            $fileBody = $legacyService->getDokumentFile($legacyId);
            $tmpPath = null;
            if ($fileBody !== null && $fileBody !== '') {
                $tmpPath = Storage::disk('local')->path('dokumente-import/'.$legacyId.'_'.uniqid('', true));
                Storage::disk('local')->makeDirectory('dokumente-import');
                file_put_contents($tmpPath, $fileBody);
            }

            try {
                if (! $existing) {
                    if ($tmpPath === null) {
                        $report['missing_file']++;
                        $report['skipped']++;

                        continue;
                    }

                    $originalName = basename((string) ($legacy['objekt'] ?? 'dokument.bin'));
                    $this->lifecycle->createDocument(
                        attributes: $attributes,
                        file: $tmpPath,
                        originalFileName: $originalName,
                        showInNewsSlider: false,
                        dispatchNews: false,
                        seedAcknowledgments: $requiresAck,
                    );
                    $report['created']++;

                    continue;
                }

                $this->lifecycle->updateMetadata($existing, $attributes, (int) $uploaderId);

                $needsFile = $existing->currentVersion === null
                    || $existing->currentVersion->getFirstMedia('document') === null;

                if ($needsFile && $tmpPath !== null) {
                    $this->lifecycle->createNewVersion(
                        document: $existing,
                        file: $tmpPath,
                        actorId: (int) $uploaderId,
                        attributes: [],
                        originalFileName: basename((string) ($legacy['objekt'] ?? 'dokument.bin')),
                        showInNewsSlider: false,
                        dispatchNews: false,
                        requireReAcknowledgment: false,
                    );
                } elseif ($needsFile) {
                    $report['missing_file']++;
                }

                if ($requiresAck && $existing->fresh(['currentVersion'])->currentVersion) {
                    app(DocumentAcknowledgmentService::class)->seedForAllAppUsers($existing->currentVersion);
                }

                $report['updated']++;
            } catch (\Throwable $e) {
                Log::error('Legacy document import failed', [
                    'legacy_id' => $legacyId,
                    'error' => $e->getMessage(),
                ]);
                $report['skipped']++;
            } finally {
                if ($tmpPath && is_file($tmpPath)) {
                    @unlink($tmpPath);
                }
            }
        }

        DocumentMatrixService::clearCountMatrixCache();

        return $report;
    }
}
