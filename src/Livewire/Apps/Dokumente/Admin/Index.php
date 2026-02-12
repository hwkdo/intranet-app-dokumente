<?php

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Admin;

use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'einstellungen';

    public ?int $editingCategoryId = null;

    public bool $showCategoryForm = false;

    public string $editingName = '';

    public int $editingSortOrder = 0;

    public function mount(): void
    {
        $this->authorize('manage-app-dokumente');
    }

    public function startEditCategory(int $id): void
    {
        $cat = DocumentCategory::find($id);
        if ($cat) {
            $this->editingCategoryId = $id;
            $this->showCategoryForm = true;
            $this->editingName = $cat->name;
            $this->editingSortOrder = $cat->sort_order;
        }
    }

    public function startNewCategory(): void
    {
        $this->editingCategoryId = null;
        $this->showCategoryForm = true;
        $this->editingName = '';
        $this->editingSortOrder = (int) DocumentCategory::max('sort_order') + 1;
    }

    public function cancelEditCategory(): void
    {
        $this->editingCategoryId = null;
        $this->showCategoryForm = false;
        $this->editingName = '';
        $this->editingSortOrder = 0;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingSortOrder' => ['required', 'integer', 'min:0'],
        ]);

        if ($this->editingCategoryId) {
            $cat = DocumentCategory::findOrFail($this->editingCategoryId);
            $cat->update([
                'name' => $this->editingName,
                'sort_order' => $this->editingSortOrder,
            ]);
            Flux::toast(heading: 'Gespeichert', text: 'Kategorie wurde aktualisiert.', variant: 'success');
        } else {
            DocumentCategory::create([
                'name' => $this->editingName,
                'sort_order' => $this->editingSortOrder,
            ]);
            Flux::toast(heading: 'Erstellt', text: 'Kategorie wurde angelegt.', variant: 'success');
        }
        $this->cancelEditCategory();
    }

    public function deleteCategory(int $id): void
    {
        $cat = DocumentCategory::find($id);
        if ($cat && $cat->documents()->count() === 0) {
            $cat->delete();
            Flux::toast(heading: 'Gelöscht', text: 'Kategorie wurde entfernt.', variant: 'success');
        } else {
            Flux::toast(heading: 'Fehler', text: 'Kategorie kann nicht gelöscht werden (enthält noch Dokumente oder existiert nicht).', variant: 'error');
        }
        $this->cancelEditCategory();
        $this->showCategoryForm = false;
    }

    public function getCategoriesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentCategory::orderBy('sort_order')->orderBy('name')->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.admin.index')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente App - Admin',
            ]);
    }
}
