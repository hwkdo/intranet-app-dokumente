<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use App\Models\Gvp;
use App\Models\User;
use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Enums\DocumentNewsTitleImageMode;
use Hwkdo\IntranetAppDokumente\Livewire\Concerns\ManagesDocuments;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Services\DocumentLifecycleService;
use Hwkdo\IntranetAppDokumente\Services\DocumentMatrixService;
use Hwkdo\IntranetAppDokumente\Support\DocumentPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    use ManagesDocuments;

    /** @var array<int, bool> */
    public array $openedGbIds = [];

    public bool $stabOpened = false;

    public bool $showDocumentListModal = false;

    /** @var Collection<int, Document> */
    public $modalDocuments;

    public string $modalSearch = '';

    public string $modalCategoryLabel = '';

    public string $modalGvpLabel = '';

    public bool $showUploadModal = false;

    public string $uploadTitle = '';

    public string $uploadDescription = '';

    public ?string $uploadGueltigBis = null;

    public bool $uploadAktiv = true;

    public ?int $uploadResponsibleId = null;

    public ?int $uploadGvpId = null;

    public ?int $uploadCategoryId = null;

    public bool $uploadRequiresAcknowledgment = false;

    public bool $uploadShowInNewsSlider = false;

    public string $uploadNewsTitleImageMode = 'auto';

    public $uploadNewsTitleImage = null;

    public $uploadFile = null;

    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
        $this->modalDocuments = collect();
        $this->uploadResponsibleId = auth()->id();

        $documentId = request()->integer('document');
        if ($documentId > 0) {
            $this->openDetail($documentId);
        }
    }

    public function openUploadModal(): void
    {
        $this->authorize('create', Document::class);
        $this->resetUploadForm();
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->resetUploadForm();
    }

    protected function resetUploadForm(): void
    {
        $this->uploadTitle = '';
        $this->uploadDescription = '';
        $this->uploadGueltigBis = null;
        $this->uploadAktiv = true;
        $this->uploadResponsibleId = auth()->id();
        $this->uploadGvpId = auth()->user()?->gvp_id;
        $this->uploadCategoryId = null;
        $this->uploadRequiresAcknowledgment = false;
        $this->uploadShowInNewsSlider = false;
        $this->uploadNewsTitleImageMode = DocumentNewsTitleImageMode::Auto->value;
        $this->uploadNewsTitleImage = null;
        $this->uploadFile = null;
        $this->resetValidation();
    }

    public function updatedUploadResponsibleId(?int $value): void
    {
        if (! auth()->user()?->can(DocumentPermissions::chooseGvp())) {
            return;
        }

        $this->uploadGvpId = $value
            ? User::query()->whereKey($value)->value('gvp_id')
            : null;
    }

    public function saveUpload(): void
    {
        $this->authorize('create', Document::class);

        if (! auth()->user()?->can(DocumentPermissions::kenntnisnahme())) {
            $this->uploadRequiresAcknowledgment = false;
        }

        $canChooseGvp = auth()->user()?->can(DocumentPermissions::chooseGvp()) ?? false;

        $rules = [
            'uploadTitle' => ['required', 'string', 'max:255'],
            'uploadDescription' => ['nullable', 'string'],
            'uploadGueltigBis' => ['nullable', 'date'],
            'uploadResponsibleId' => ['required', 'exists:users,id'],
            'uploadCategoryId' => ['required', 'exists:intranet_app_dokumente_categories,id'],
            'uploadFile' => ['required', 'file', 'max:51200'],
            'uploadNewsTitleImageMode' => ['required', 'in:auto,custom,default'],
        ];

        if ($canChooseGvp) {
            $rules['uploadGvpId'] = ['required', 'exists:gvps,id'];
        }

        if ($this->uploadNewsTitleImageMode === DocumentNewsTitleImageMode::Custom->value) {
            $rules['uploadNewsTitleImage'] = ['required', 'image', 'max:10240'];
        }

        $this->validate($rules);

        $user = User::findOrFail($this->uploadResponsibleId);
        $gvpId = $canChooseGvp ? $this->uploadGvpId : $user->gvp_id;

        app(DocumentLifecycleService::class)->createDocument(
            attributes: [
                'title' => $this->uploadTitle,
                'description' => $this->uploadDescription ?: null,
                'gueltig_bis' => $this->uploadGueltigBis ?: null,
                'aktiv' => $this->uploadAktiv,
                'uploader_id' => (int) auth()->id(),
                'responsible_id' => $this->uploadResponsibleId,
                'gvp_id' => $gvpId,
                'category_id' => $this->uploadCategoryId,
                'requires_acknowledgment' => $this->uploadRequiresAcknowledgment,
            ],
            file: $this->uploadFile,
            showInNewsSlider: $this->uploadShowInNewsSlider,
            newsTitleImageMode: DocumentNewsTitleImageMode::from($this->uploadNewsTitleImageMode),
            customNewsTitleImage: $this->uploadNewsTitleImageMode === DocumentNewsTitleImageMode::Custom->value
                ? $this->uploadNewsTitleImage
                : null,
        );

        Flux::toast(heading: 'Hochgeladen', text: 'Dokument wurde gespeichert.', variant: 'success');
        $this->closeUploadModal();
    }

    #[Computed]
    public function gvpsForSelect(): Collection
    {
        return Gvp::query()
            ->orderBy('kuerzel')
            ->orderBy('nummer')
            ->orderBy('name')
            ->get(['id', 'kuerzel', 'nummer', 'name'])
            ->mapWithKeys(fn (Gvp $gvp) => [$gvp->id => $gvp->bezeichnung]);
    }

    #[Computed]
    public function matrixService(): DocumentMatrixService
    {
        return app(DocumentMatrixService::class);
    }

    #[Computed]
    public function gvpStructure(): array
    {
        return $this->matrixService->getGvpStructure();
    }

    #[Computed]
    public function categories(): Collection
    {
        return $this->matrixService->getCategories();
    }

    #[Computed]
    public function allDocumentsCount(): int
    {
        return (int) ($this->countMatrix['all'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function countMatrix(): array
    {
        return $this->matrixService->getCountMatrix();
    }

    public function toggleGb(int $id): void
    {
        if (in_array($id, $this->openedGbIds, true)) {
            $this->openedGbIds = array_values(array_diff($this->openedGbIds, [$id]));
        } else {
            $this->openedGbIds = array_values(array_merge($this->openedGbIds, [$id]));
        }
    }

    public function toggleStab(): void
    {
        $this->stabOpened = ! $this->stabOpened;
    }

    public function isGbOpened(int $id): bool
    {
        return in_array($id, $this->openedGbIds, true);
    }

    /**
     * @return array<int>
     */
    public function getStabGvpIds(): array
    {
        return $this->matrixService->getStabGvpIds();
    }

    public function openDocumentListModal(?int $categoryId, string $gvpIdsComma, string $categoryLabel, string $gvpLabel): void
    {
        $gvpIds = array_filter(array_map('intval', explode(',', $gvpIdsComma)));
        $this->modalSearch = '';
        $this->modalDocuments = $this->matrixService->getDocumentsForCell($categoryId, $gvpIds);
        $this->modalCategoryLabel = $categoryLabel;
        $this->modalGvpLabel = $gvpLabel;
        $this->showDocumentListModal = true;
    }

    public function openDocumentListModalByCategory(int $categoryId, string $categoryLabel): void
    {
        $this->modalSearch = '';
        $this->modalDocuments = $this->matrixService->getDocumentsByCategory($categoryId);
        $this->modalCategoryLabel = $categoryLabel;
        $this->modalGvpLabel = 'Alle';
        $this->showDocumentListModal = true;
    }

    public function openDocumentListModalAll(): void
    {
        $this->modalSearch = '';
        $this->modalDocuments = $this->matrixService->getAllGueltigeDocuments();
        $this->modalCategoryLabel = 'Alle';
        $this->modalGvpLabel = 'Alle';
        $this->showDocumentListModal = true;
    }

    public function closeDocumentListModal(): void
    {
        $this->showDocumentListModal = false;
        $this->modalSearch = '';
        $this->modalDocuments = collect();
    }

    /**
     * @return Collection<int, Document>
     */
    #[Computed]
    public function filteredModalDocuments(): Collection
    {
        $documents = collect($this->modalDocuments ?? []);
        $search = trim($this->modalSearch);

        if ($search === '') {
            return $documents->values();
        }

        return $documents
            ->filter(fn (Document $document): bool => str_contains(
                mb_strtolower($document->title),
                mb_strtolower($search),
            ))
            ->values();
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.index')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente App',
            ]);
    }
}
