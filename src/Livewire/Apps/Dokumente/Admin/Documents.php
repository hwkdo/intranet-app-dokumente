<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Admin;

use Hwkdo\IntranetAppDokumente\Livewire\Concerns\ManagesDocuments;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Documents extends Component
{
    use ManagesDocuments;
    use WithPagination;

    public string $search = '';

    public bool $showTrashed = false;

    public function mount(): void
    {
        $this->authorize('manage-app-dokumente');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function documents()
    {
        return Document::query()
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->with(['category', 'uploader', 'responsible', 'currentVersion'])
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->orderByDesc('updated_at')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.admin.documents');
    }
}
