<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Concerns;

use App\Models\User;
use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Enums\DocumentNewsTitleImageMode;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Hwkdo\IntranetAppDokumente\Services\DocumentLifecycleService;
use Hwkdo\IntranetAppDokumente\Support\DocumentPermissions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

trait ManagesDocuments
{
    use ManagesAcknowledgmentReport;
    use WithFileUploads;

    public bool $showDetailModal = false;

    public ?int $detailDocumentId = null;

    public bool $detailDownloadClicked = false;

    public bool $showExtendModal = false;

    public string $extendMode = 'auto';

    public ?string $extendManualDate = null;

    public bool $showUpdateModal = false;

    public string $updateTitle = '';

    public string $updateDescription = '';

    public ?string $updateGueltigBis = null;

    public bool $updateAktiv = true;

    public ?int $updateResponsibleId = null;

    public ?int $updateCategoryId = null;

    public bool $updateRequiresAcknowledgment = false;

    public bool $updateRequireReAcknowledgment = true;

    public bool $updateShowInNewsSlider = false;

    public string $updateNewsTitleImageMode = 'auto';

    public $updateNewsTitleImage = null;

    public $updateFile = null;

    public function openDetail(int $documentId): void
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $this->authorize('view', $document);
        $this->detailDocumentId = $documentId;
        $this->detailDownloadClicked = app(DocumentAcknowledgmentService::class)
            ->hasDownloadedForAcknowledgment($documentId);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->detailDocumentId = null;
        $this->detailDownloadClicked = false;
    }

    public function markDetailDownloaded(?int $versionId = null): void
    {
        if (! $this->detailDocumentId) {
            return;
        }

        $document = Document::query()->withTrashed()->find($this->detailDocumentId);
        if (! $document) {
            return;
        }

        $version = null;
        if ($versionId !== null) {
            $version = $document->versions()->whereKey($versionId)->first();
        }

        $ack = app(DocumentAcknowledgmentService::class);
        $ack->markDownloadedForAcknowledgment($document, $version);
        $this->detailDownloadClicked = $ack->hasDownloadedForAcknowledgment((int) $document->id);
    }

    public function getDetailNeedsAcknowledgmentProperty(): bool
    {
        $document = $this->detailDocument;
        if (! $document) {
            return false;
        }

        return app(DocumentAcknowledgmentService::class)
            ->needsAcknowledgment($document, (int) auth()->id());
    }

    public function openExtendModal(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $this->authorize('review', $document);
        $this->detailDocumentId = $documentId;
        $this->extendMode = 'auto';
        $this->extendManualDate = null;
        $this->showExtendModal = true;
    }

    public function saveExtend(): void
    {
        $document = Document::findOrFail($this->detailDocumentId);
        $this->authorize('review', $document);

        try {
            if ($this->extendMode === 'manual') {
                $this->validate([
                    'extendManualDate' => ['required', 'date', 'after:today'],
                ]);
                app(DocumentLifecycleService::class)->extendValidity(
                    $document,
                    (int) auth()->id(),
                    automatic: false,
                    manualDate: Carbon::parse($this->extendManualDate),
                );
            } else {
                app(DocumentLifecycleService::class)->extendValidity(
                    $document,
                    (int) auth()->id(),
                    automatic: true,
                );
            }
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['extendManualDate' => $e->getMessage()]);
        }

        Flux::toast(heading: 'Verlängert', text: 'Gültigkeit wurde angepasst.', variant: 'success');
        $this->showExtendModal = false;
    }

    public function openUpdateModal(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $this->authorize('update', $document);
        $this->detailDocumentId = $documentId;
        $this->updateTitle = $document->title;
        $this->updateDescription = (string) ($document->description ?? '');
        $this->updateGueltigBis = $document->gueltig_bis?->format('Y-m-d');
        $this->updateAktiv = $document->aktiv;
        $this->updateResponsibleId = $document->responsible_id;
        $this->updateCategoryId = $document->category_id;
        $this->updateRequiresAcknowledgment = $document->requires_acknowledgment;
        $this->updateRequireReAcknowledgment = true;
        $this->updateShowInNewsSlider = false;
        $this->updateNewsTitleImageMode = DocumentNewsTitleImageMode::Auto->value;
        $this->updateNewsTitleImage = null;
        $this->updateFile = null;
        $this->showUpdateModal = true;
    }

    public function saveUpdateVersion(): void
    {
        $document = Document::findOrFail($this->detailDocumentId);
        $this->authorize('update', $document);

        $rules = [
            'updateTitle' => ['required', 'string', 'max:255'],
            'updateDescription' => ['nullable', 'string'],
            'updateGueltigBis' => ['nullable', 'date'],
            'updateResponsibleId' => ['required', 'exists:users,id'],
            'updateCategoryId' => ['required', 'exists:intranet_app_dokumente_categories,id'],
            'updateFile' => ['required', 'file', 'max:51200'],
            'updateNewsTitleImageMode' => ['required', 'in:auto,custom,default'],
        ];

        if ($this->updateNewsTitleImageMode === DocumentNewsTitleImageMode::Custom->value) {
            $rules['updateNewsTitleImage'] = ['required', 'image', 'max:10240'];
        }

        if (! auth()->user()?->can(DocumentPermissions::kenntnisnahme())) {
            $this->updateRequiresAcknowledgment = $document->requires_acknowledgment;
        }

        if (! $this->updateRequiresAcknowledgment) {
            $this->updateRequireReAcknowledgment = false;
        }

        $this->validate($rules);

        $userClass = config('intranet-app-dokumente.user_model', User::class);
        $responsible = $userClass::findOrFail($this->updateResponsibleId);

        app(DocumentLifecycleService::class)->createNewVersion(
            document: $document,
            file: $this->updateFile,
            actorId: (int) auth()->id(),
            attributes: [
                'title' => $this->updateTitle,
                'description' => $this->updateDescription ?: null,
                'gueltig_bis' => $this->updateGueltigBis ?: null,
                'aktiv' => $this->updateAktiv,
                'responsible_id' => $this->updateResponsibleId,
                'gvp_id' => $responsible->gvp_id,
                'category_id' => $this->updateCategoryId,
                'requires_acknowledgment' => $this->updateRequiresAcknowledgment,
            ],
            showInNewsSlider: $this->updateShowInNewsSlider,
            newsTitleImageMode: DocumentNewsTitleImageMode::from($this->updateNewsTitleImageMode),
            customNewsTitleImage: $this->updateNewsTitleImageMode === DocumentNewsTitleImageMode::Custom->value
                ? $this->updateNewsTitleImage
                : null,
            requireReAcknowledgment: $this->updateRequiresAcknowledgment && $this->updateRequireReAcknowledgment,
        );

        Flux::toast(heading: 'Aktualisiert', text: 'Neue Version wurde angelegt.', variant: 'success');
        $this->showUpdateModal = false;
        $this->updateFile = null;
        $this->updateNewsTitleImage = null;
    }

    public function deleteDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $this->authorize('delete', $document);
        app(DocumentLifecycleService::class)->softDelete($document, (int) auth()->id());
        Flux::toast(heading: 'Gelöscht', text: 'Dokument wurde soft-gelöscht.', variant: 'success');
        $this->closeDetail();
    }

    public function getDetailDocumentProperty(): ?Document
    {
        if (! $this->detailDocumentId) {
            return null;
        }

        return Document::query()
            ->withTrashed()
            ->with([
                'category',
                'gvp',
                'uploader',
                'responsible',
                'currentVersion.media',
                'versions.uploader',
                'versions.media',
                'histories.user',
            ])
            ->find($this->detailDocumentId);
    }

    public function getCategoriesForSelectProperty(): Collection
    {
        return DocumentCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function getUsersForSelectProperty(): Collection
    {
        $userClass = config('intranet-app-dokumente.user_model', User::class);

        return $userClass::query()
            ->where('active', true)
            ->orderBy('vorname')
            ->orderBy('nachname')
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => trim(($u->vorname ?? '').' '.($u->nachname ?? '')) ?: ($u->name ?? (string) $u->id)]);
    }
}
