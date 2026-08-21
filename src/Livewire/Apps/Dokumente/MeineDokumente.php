<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Hwkdo\IntranetAppDokumente\Livewire\Concerns\ManagesDocuments;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MeineDokumente extends Component
{
    use ManagesDocuments;
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function documents()
    {
        $userId = (int) auth()->id();

        return Document::query()
            ->with(['category', 'currentVersion', 'uploader', 'responsible'])
            ->where(function ($q) use ($userId): void {
                $q->where('uploader_id', $userId)
                    ->orWhere('responsible_id', $userId);
            })
            ->when($this->search !== '', function ($q): void {
                $q->where('title', 'like', '%'.$this->search.'%');
            })
            ->orderByDesc('updated_at')
            ->paginate(20);
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.meine-dokumente')
            ->layout('components.layouts.app', [
                'title' => 'Meine Dokumente',
            ]);
    }
}
