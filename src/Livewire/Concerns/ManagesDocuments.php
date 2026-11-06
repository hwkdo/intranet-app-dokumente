<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Concerns;

use App\Models\User;
use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Enums\AcknowledgmentMethod;
use Hwkdo\IntranetAppDokumente\Enums\DocumentNewsTitleImageMode;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
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

    public bool $showEditModal = false;

    public string $updateTitle = '';

    public string $updateDescription = '';

    public ?string $updateGueltigBis = null;

    public bool $updateAktiv = true;

    public ?int $updateResponsibleId = null;

    public ?int $updateCategoryId = null;

    public bool $updateRequiresAcknowledgment = false;

    public string $updateAcknowledgmentMethod = 'password';

    public ?string $updateFormwerkFormUrl = null;

    public string $updateFormwerkUrlMode = 'standard';

    public bool $updateFormwerkUseCustomPayload = false;

    /** @var list<array{key: string, value: string}> */
    public array $updateFormwerkPayload = [];

    public bool $updateIsOnboardingIt = false;

    public bool $updateIsOnboardingPerso = false;

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
        $userId = auth()->id();
        if ($document === null || $userId === null) {
            return false;
        }

        return app(DocumentAcknowledgmentService::class)
            ->needsAcknowledgment($document, (int) $userId);
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
        $this->fillUpdateFields($document);
        $this->updateRequireReAcknowledgment = true;
        $this->updateShowInNewsSlider = false;
        $this->updateNewsTitleImageMode = DocumentNewsTitleImageMode::Auto->value;
        $this->updateNewsTitleImage = null;
        $this->updateFile = null;
        $this->showUpdateModal = true;
    }

    public function openEditModal(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $this->authorize('update', $document);
        $this->fillUpdateFields($document);
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $document = Document::findOrFail($this->detailDocumentId);
        $this->authorize('update', $document);

        $rules = [
            'updateTitle' => ['required', 'string', 'max:255'],
            'updateDescription' => ['nullable', 'string'],
            'updateGueltigBis' => ['nullable', 'date'],
            'updateResponsibleId' => ['required', 'exists:users,id'],
            'updateCategoryId' => ['required', 'exists:intranet_app_dokumente_categories,id'],
        ];

        if (! auth()->user()?->can(DocumentPermissions::kenntnisnahme())) {
            $this->updateRequiresAcknowledgment = $document->requires_acknowledgment;
            $this->updateAcknowledgmentMethod = $document->acknowledgment_method?->value ?? AcknowledgmentMethod::Password->value;
            $this->updateFormwerkFormUrl = $document->formwerk_form_url;
            $this->updateFormwerkUrlMode = $this->formwerkUrlModeFor($document->formwerk_form_url);
            $this->updateFormwerkUseCustomPayload = (bool) $document->formwerk_use_custom_payload;
            $this->updateFormwerkPayload = $this->formwerkPayloadRows($document->formwerk_payload);
        }

        $this->blankFormwerkUrl('update');
        $this->validate(array_merge($rules, $this->acknowledgmentValidationRules('update')));

        $userClass = config('intranet-app-dokumente.user_model', User::class);
        $responsible = $userClass::findOrFail($this->updateResponsibleId);

        app(DocumentLifecycleService::class)->editMetadata(
            document: $document,
            attributes: [
                'title' => $this->updateTitle,
                'description' => $this->updateDescription ?: null,
                'gueltig_bis' => $this->updateGueltigBis ?: null,
                'aktiv' => $this->updateAktiv,
                'responsible_id' => $this->updateResponsibleId,
                'gvp_id' => $responsible->gvp_id,
                'category_id' => $this->updateCategoryId,
                'requires_acknowledgment' => $this->updateRequiresAcknowledgment,
                ...$this->formwerkAcknowledgmentAttributes('update'),
            ],
            actorId: (int) auth()->id(),
        );

        Flux::toast(heading: 'Gespeichert', text: 'Dokument wurde bearbeitet.', variant: 'success');
        $this->showEditModal = false;
    }

    protected function fillUpdateFields(Document $document): void
    {
        $this->detailDocumentId = $document->id;
        $this->updateTitle = $document->title;
        $this->updateDescription = (string) ($document->description ?? '');
        $this->updateGueltigBis = $document->gueltig_bis?->format('Y-m-d');
        $this->updateAktiv = $document->aktiv;
        $this->updateResponsibleId = $document->responsible_id;
        $this->updateCategoryId = $document->category_id;
        $this->updateRequiresAcknowledgment = $document->requires_acknowledgment;
        $this->updateAcknowledgmentMethod = $document->acknowledgment_method?->value ?? AcknowledgmentMethod::Password->value;
        $this->updateFormwerkFormUrl = $document->formwerk_form_url;
        $this->updateFormwerkUrlMode = $this->formwerkUrlModeFor($document->formwerk_form_url);
        $this->updateFormwerkUseCustomPayload = (bool) $document->formwerk_use_custom_payload;
        $this->updateFormwerkPayload = $this->formwerkPayloadRows($document->formwerk_payload);
        $this->updateIsOnboardingIt = (bool) $document->is_onboarding_it;
        $this->updateIsOnboardingPerso = (bool) $document->is_onboarding_perso;
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
            $this->updateAcknowledgmentMethod = $document->acknowledgment_method?->value ?? AcknowledgmentMethod::Password->value;
            $this->updateFormwerkFormUrl = $document->formwerk_form_url;
            $this->updateFormwerkUrlMode = $this->formwerkUrlModeFor($document->formwerk_form_url);
            $this->updateFormwerkUseCustomPayload = (bool) $document->formwerk_use_custom_payload;
            $this->updateFormwerkPayload = $this->formwerkPayloadRows($document->formwerk_payload);
            $this->updateIsOnboardingIt = (bool) $document->is_onboarding_it;
            $this->updateIsOnboardingPerso = (bool) $document->is_onboarding_perso;
        }

        if (! $this->updateRequiresAcknowledgment) {
            $this->updateRequireReAcknowledgment = false;
        }

        $this->blankFormwerkUrl('update');
        $this->validate(array_merge($rules, $this->acknowledgmentValidationRules('update')));

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
                ...$this->formwerkAcknowledgmentAttributes('update'),
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

    public function adoptResponsibleGvp(): void
    {
        if (! $this->detailDocumentId) {
            return;
        }

        $document = Document::query()->with(['gvp', 'responsible.gvp'])->findOrFail($this->detailDocumentId);
        $this->authorize('update', $document);

        if (! $document->hasInvalidStoredGvp()) {
            Flux::toast(heading: 'Keine Änderung', text: 'Die gespeicherte GVP ist gültig.', variant: 'warning');

            return;
        }

        $fallback = $document->responsibleFallbackGvp();
        if ($fallback === null) {
            Flux::toast(
                heading: 'Nicht möglich',
                text: 'Der Verantwortliche hat keine gültige GVP.',
                variant: 'danger',
            );

            return;
        }

        $document->update(['gvp_id' => $fallback->id]);

        Flux::toast(
            heading: 'GVP aktualisiert',
            text: 'Gespeicherte GVP wurde auf „'.$fallback->name.'“ gesetzt.',
            variant: 'success',
        );
    }

    public function updatedUpdateFormwerkUseCustomPayload(bool $value): void
    {
        if ($value && $this->updateFormwerkPayload === []) {
            $this->updateFormwerkPayload = [['key' => '', 'value' => '']];
        }
    }

    public function addFormwerkPayloadRow(string $target): void
    {
        $property = $target === 'upload' ? 'uploadFormwerkPayload' : 'updateFormwerkPayload';
        $rows = $this->{$property};
        $rows[] = ['key' => '', 'value' => ''];
        $this->{$property} = $rows;
    }

    public function removeFormwerkPayloadRow(string $target, int $index): void
    {
        $property = $target === 'upload' ? 'uploadFormwerkPayload' : 'updateFormwerkPayload';
        $rows = $this->{$property};
        unset($rows[$index]);
        $this->{$property} = array_values($rows);
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
                'responsible.gvp',
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

    /**
     * @return array<string, mixed>
     */
    protected function acknowledgmentValidationRules(string $prefix): array
    {
        if (! $this->{$prefix.'RequiresAcknowledgment'}) {
            return [];
        }

        $rules = [
            $prefix.'AcknowledgmentMethod' => ['required', 'in:password,formwerk'],
        ];

        if ($this->{$prefix.'AcknowledgmentMethod'} !== AcknowledgmentMethod::Formwerk->value) {
            return $rules;
        }

        $rules[$prefix.'FormwerkUrlMode'] = ['required', 'in:standard,custom'];

        if ($this->{$prefix.'FormwerkUrlMode'} === 'custom') {
            $rules[$prefix.'FormwerkFormUrl'] = ['required', 'string', 'max:2048', 'url'];
        }

        if ($this->{$prefix.'FormwerkUseCustomPayload'}) {
            $rules[$prefix.'FormwerkPayload'] = ['array'];
            $rules[$prefix.'FormwerkPayload.*.key'] = ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]*$/'];
            $rules[$prefix.'FormwerkPayload.*.value'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    protected function blankFormwerkUrl(string $prefix): void
    {
        $property = $prefix.'FormwerkFormUrl';
        if ($this->{$prefix.'FormwerkUrlMode'} !== 'custom' || trim((string) ($this->{$property} ?? '')) === '') {
            $this->{$property} = null;
        }
    }

    protected function formwerkUrlModeFor(?string $url): string
    {
        return trim((string) $url) !== '' ? 'custom' : 'standard';
    }

    public function getFormwerkStandardFormUrlProperty(): ?string
    {
        $url = trim((string) (IntranetAppDokumenteSettings::resolvedAppSettings()->formwerkAcknowledgmentFormUrl ?? ''));

        return $url !== '' ? $url : null;
    }

    /**
     * @return array{
     *     acknowledgment_method: string,
     *     formwerk_form_url: ?string,
     *     formwerk_use_custom_payload: bool,
     *     formwerk_payload: ?array<string, string>,
     *     is_onboarding_it: bool,
     *     is_onboarding_perso: bool
     * }
     */
    protected function formwerkAcknowledgmentAttributes(string $prefix): array
    {
        $onboarding = [
            'is_onboarding_it' => (bool) $this->{$prefix.'IsOnboardingIt'},
            'is_onboarding_perso' => (bool) $this->{$prefix.'IsOnboardingPerso'},
        ];

        $requires = (bool) $this->{$prefix.'RequiresAcknowledgment'};
        $method = (string) $this->{$prefix.'AcknowledgmentMethod'};
        if (! $requires || $method !== AcknowledgmentMethod::Formwerk->value) {
            return [
                'acknowledgment_method' => AcknowledgmentMethod::Password->value,
                'formwerk_form_url' => null,
                'formwerk_use_custom_payload' => false,
                'formwerk_payload' => null,
                ...$onboarding,
            ];
        }

        $payload = [];
        if ($this->{$prefix.'FormwerkUseCustomPayload'}) {
            foreach ($this->{$prefix.'FormwerkPayload'} as $row) {
                $key = trim((string) ($row['key'] ?? ''));
                if ($key === '' || in_array($key, ['kenntnisnahme_ref', 'document_title'], true)) {
                    continue;
                }
                $payload[$key] = trim((string) ($row['value'] ?? ''));
            }
        }

        $url = trim((string) ($this->{$prefix.'FormwerkFormUrl'} ?? ''));

        return [
            'acknowledgment_method' => AcknowledgmentMethod::Formwerk->value,
            'formwerk_form_url' => $url !== '' ? $url : null,
            'formwerk_use_custom_payload' => $payload !== [],
            'formwerk_payload' => $payload !== [] ? $payload : null,
            ...$onboarding,
        ];
    }

    /**
     * @param  array<string, string>|null  $payload
     * @return list<array{key: string, value: string}>
     */
    protected function formwerkPayloadRows(?array $payload): array
    {
        $rows = [];
        foreach ($payload ?? [] as $key => $value) {
            $rows[] = ['key' => (string) $key, 'value' => (string) $value];
        }

        return $rows;
    }
}
