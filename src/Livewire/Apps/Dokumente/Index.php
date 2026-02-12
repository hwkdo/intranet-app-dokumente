<?php

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use App\Models\User;
use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Hwkdo\IntranetAppDokumente\Services\DocumentMatrixService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    /** @var array<int, bool> opened GB row ids */
    public array $openedGbIds = [];

    public bool $stabOpened = false;

    public bool $showDocumentListModal = false;

    /** @var \Illuminate\Support\Collection<int, Document> */
    public $modalDocuments;

    public string $modalCategoryLabel = '';

    public string $modalGvpLabel = '';

    public bool $showUploadModal = false;

    public string $uploadTitle = '';

    public string $uploadDescription = '';

    public ?string $uploadGueltigBis = null;

    public bool $uploadAktiv = true;

    public ?int $uploadResponsibleId = null;

    public ?int $uploadCategoryId = null;

    public $uploadFile = null;

    public bool $showEditModal = false;

    public ?int $editingDocumentId = null;

    public string $editTitle = '';

    public string $editDescription = '';

    public ?string $editGueltigBis = null;

    public bool $editAktiv = true;

    public ?int $editResponsibleId = null;

    public ?int $editCategoryId = null;

    public $editFile = null;

    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
        $this->modalDocuments = collect();
        $this->uploadResponsibleId = auth()->id();
    }

    public function openUploadModal(): void
    {
        $this->authorize('upload-app-dokumente');
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
        $this->uploadCategoryId = null;
        $this->uploadFile = null;
        $this->resetValidation();
    }

    public function saveUpload(): void
    {
        $this->authorize('upload-app-dokumente');
        $this->validate([
            'uploadTitle' => ['required', 'string', 'max:255'],
            'uploadDescription' => ['nullable', 'string'],
            'uploadGueltigBis' => ['nullable', 'date'],
            'uploadResponsibleId' => ['required', 'exists:users,id'],
            'uploadCategoryId' => ['required', 'exists:intranet_app_dokumente_categories,id'],
            'uploadFile' => ['required', 'file', 'max:51200'],
        ]);

        $user = User::findOrFail($this->uploadResponsibleId);

        $document = Document::create([
            'title' => $this->uploadTitle,
            'description' => $this->uploadDescription ?: null,
            'gueltig_bis' => $this->uploadGueltigBis ?: null,
            'aktiv' => $this->uploadAktiv,
            'uploader_id' => auth()->id(),
            'responsible_id' => $this->uploadResponsibleId,
            'gvp_id' => $user->gvp_id,
            'category_id' => $this->uploadCategoryId,
        ]);

        $document->addMedia($this->uploadFile->getRealPath())
            ->usingFileName($this->uploadFile->getClientOriginalName())
            ->toMediaCollection('document');

        Flux::toast(heading: 'Hochgeladen', text: 'Dokument wurde gespeichert.', variant: 'success');
        $this->closeUploadModal();
    }

    public function openEditModal(int $documentId): void
    {
        $this->authorize('manage-app-dokumente');
        $doc = Document::findOrFail($documentId);
        $this->editingDocumentId = $documentId;
        $this->editTitle = $doc->title;
        $this->editDescription = $doc->description ?? '';
        $this->editGueltigBis = $doc->gueltig_bis?->format('Y-m-d');
        $this->editAktiv = $doc->aktiv;
        $this->editResponsibleId = $doc->responsible_id;
        $this->editCategoryId = $doc->category_id;
        $this->editFile = null;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingDocumentId = null;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->authorize('manage-app-dokumente');
        $doc = Document::findOrFail($this->editingDocumentId);
        $this->validate([
            'editTitle' => ['required', 'string', 'max:255'],
            'editDescription' => ['nullable', 'string'],
            'editGueltigBis' => ['nullable', 'date'],
            'editResponsibleId' => ['required', 'exists:users,id'],
            'editCategoryId' => ['required', 'exists:intranet_app_dokumente_categories,id'],
            'editFile' => ['nullable', 'file', 'max:51200'],
        ]);

        $user = User::findOrFail($this->editResponsibleId);
        $doc->update([
            'title' => $this->editTitle,
            'description' => $this->editDescription ?: null,
            'gueltig_bis' => $this->editGueltigBis ?: null,
            'aktiv' => $this->editAktiv,
            'responsible_id' => $this->editResponsibleId,
            'gvp_id' => $user->gvp_id,
            'category_id' => $this->editCategoryId,
        ]);

        if ($this->editFile) {
            $doc->clearMediaCollection('document');
            $doc->addMedia($this->editFile->getRealPath())
                ->usingFileName($this->editFile->getClientOriginalName())
                ->toMediaCollection('document');
        }

        Flux::toast(heading: 'Gespeichert', text: 'Dokument wurde aktualisiert.', variant: 'success');
        $this->closeEditModal();
        $this->showDocumentListModal = false;
    }

    #[Computed]
    public function usersForSelect(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('active', true)
            ->orderBy('vorname')
            ->orderBy('nachname')
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->vorname.' '.$u->nachname]);
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
    public function categories(): \Illuminate\Support\Collection
    {
        return $this->matrixService->getCategories();
    }

    #[Computed]
    public function allDocumentsCount(): int
    {
        return (int) ($this->countMatrix['all'] ?? 0);
    }

    /**
     * Zähler-Matrix (eine Abfrage) für schnelles Rendern der Tabelle.
     *
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

    public function getDocumentsForCell(?int $categoryId, array $gvpIds): \Illuminate\Support\Collection
    {
        return $this->matrixService->getDocumentsForCell($categoryId, $gvpIds);
    }

    public function getDocumentsForGvpRecursive(?int $categoryId, int $gvpId): \Illuminate\Support\Collection
    {
        return $this->matrixService->getDocumentsForGvpRecursive($categoryId, $gvpId);
    }

    public function getDocumentsForGvpDirect(?int $categoryId, int $gvpId): \Illuminate\Support\Collection
    {
        return $this->matrixService->getDocumentsForGvpDirect($categoryId, $gvpId);
    }

    public function getDocumentsForStab(?int $categoryId): \Illuminate\Support\Collection
    {
        return $this->matrixService->getDocumentsForStab($categoryId);
    }

    public function getStabGvpIds(): array
    {
        return $this->matrixService->getStabGvpIds();
    }

    /**
     * @param  string  $gvpIdsComma  Comma-separated GVP IDs
     */
    public function openDocumentListModal(?int $categoryId, string $gvpIdsComma, string $categoryLabel, string $gvpLabel): void
    {
        $gvpIds = array_filter(array_map('intval', explode(',', $gvpIdsComma)));
        $this->modalDocuments = $this->matrixService->getDocumentsForCell($categoryId, $gvpIds);
        $this->modalCategoryLabel = $categoryLabel;
        $this->modalGvpLabel = $gvpLabel;
        $this->showDocumentListModal = true;
    }

    public function openDocumentListModalByCategory(int $categoryId, string $categoryLabel): void
    {
        $this->modalDocuments = $this->matrixService->getDocumentsByCategory($categoryId);
        $this->modalCategoryLabel = $categoryLabel;
        $this->modalGvpLabel = 'Alle';
        $this->showDocumentListModal = true;
    }

    public function openDocumentListModalAll(): void
    {
        $this->modalDocuments = $this->matrixService->getAllGueltigeDocuments();
        $this->modalCategoryLabel = 'Alle';
        $this->modalGvpLabel = 'Alle';
        $this->showDocumentListModal = true;
    }

    public function closeDocumentListModal(): void
    {
        $this->showDocumentListModal = false;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.index')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente App',
            ]);
    }
}
