<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Hwkdo\IntranetAppDokumente\Search\DocumentSearch;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Search extends Component
{
    public string $searchQuery = '';

    /**
     * @return Collection<int, \Hwkdo\IntranetAppDokumente\Models\Document>
     */
    #[Computed]
    public function results(): Collection
    {
        if (strlen(trim($this->searchQuery)) < 2) {
            return collect();
        }

        return DocumentSearch::query(trim($this->searchQuery), 50);
    }

    public function render()
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.search');
    }
}
