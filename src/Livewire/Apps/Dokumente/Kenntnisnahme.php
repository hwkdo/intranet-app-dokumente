<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Services\AcknowledgmentOverviewService;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Kenntnisnahme extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $teamScope = AcknowledgmentOverviewService::SCOPE_DIRECT;

    public string $teamSearch = '';

    public bool $showEmployeeDetail = false;

    public ?int $selectedEmployeeId = null;

    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['all', 'pending', 'acknowledged'], true)) {
            $this->status = 'all';
        }

        $this->resetPage();
    }

    public function updatedTeamSearch(): void
    {
        $this->resetPage(pageName: 'teamPage');
    }

    public function updatedTeamScope(): void
    {
        if (! in_array($this->teamScope, [AcknowledgmentOverviewService::SCOPE_DIRECT, AcknowledgmentOverviewService::SCOPE_ALL], true)) {
            $this->teamScope = AcknowledgmentOverviewService::SCOPE_DIRECT;
        }

        $this->closeEmployee();
        $this->resetPage(pageName: 'teamPage');
    }

    public function markDownloaded(int $documentId): void
    {
        $document = Document::query()->find($documentId);
        if ($document === null) {
            return;
        }

        $this->authorize('view', $document);

        app(DocumentAcknowledgmentService::class)->markDownloadedForAcknowledgment($document);
    }

    public function hasDownloaded(int $documentId): bool
    {
        return app(DocumentAcknowledgmentService::class)->hasDownloadedForAcknowledgment($documentId);
    }

    public function openEmployee(int $userId): void
    {
        $member = $this->teamMembersQuery()->whereKey($userId)->first();
        if ($member === null) {
            abort(403);
        }

        $this->selectedEmployeeId = (int) $member->id;
        $this->showEmployeeDetail = true;
    }

    public function closeEmployee(): void
    {
        $this->showEmployeeDetail = false;
        $this->selectedEmployeeId = null;
    }

    /**
     * @return array{led: Collection<int, Gvp>, canViewTeam: bool, scopeChoice: bool, label: string}
     */
    #[Computed]
    public function leadership(): array
    {
        return app(AcknowledgmentOverviewService::class)->leadership((int) auth()->id());
    }

    #[Computed]
    public function documents()
    {
        $userId = (int) auth()->id();

        return Document::query()
            ->requiringCurrentAcknowledgment()
            ->with([
                'category',
                'currentVersion.acknowledgments' => function ($query) use ($userId): void {
                    $query->where('user_id', $userId);
                },
            ])
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $query->where('title', 'like', '%'.trim($this->search).'%');
            })
            ->when($this->status === 'pending', function (Builder $query) use ($userId): void {
                $query->whereDoesntHave('currentVersion.acknowledgments', function (Builder $acknowledgments) use ($userId): void {
                    $acknowledgments->where('user_id', $userId);
                });
            })
            ->when($this->status === 'acknowledged', function (Builder $query) use ($userId): void {
                $query->whereHas('currentVersion.acknowledgments', function (Builder $acknowledgments) use ($userId): void {
                    $acknowledgments->where('user_id', $userId);
                });
            })
            ->orderBy('title')
            ->paginate(15);
    }

    #[Computed]
    public function teamMembers()
    {
        if (! $this->leadership['canViewTeam']) {
            return null;
        }

        return $this->teamMembersQuery()
            ->with('gvp')
            ->when(trim($this->teamSearch) !== '', function (Builder $query): void {
                $term = '%'.trim($this->teamSearch).'%';
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('vorname', 'like', $term)
                        ->orWhere('nachname', 'like', $term);
                });
            })
            ->paginate(15, pageName: 'teamPage');
    }

    /**
     * @return array<int, array{open: int, done: int}>
     */
    #[Computed]
    public function teamCounts(): array
    {
        $page = $this->teamMembers;
        if ($page === null) {
            return [];
        }

        return app(AcknowledgmentOverviewService::class)->statusCounts($page->getCollection());
    }

    #[Computed]
    public function selectedEmployee(): ?User
    {
        if (! $this->showEmployeeDetail || $this->selectedEmployeeId === null) {
            return null;
        }

        $member = $this->teamMembersQuery()->with('gvp')->whereKey($this->selectedEmployeeId)->first();

        return $member instanceof User ? $member : null;
    }

    /**
     * @return list<array{document: Document, acknowledged: bool, acknowledged_at: ?Carbon}>
     */
    #[Computed]
    public function selectedEmployeeDocuments(): array
    {
        $employee = $this->selectedEmployee;
        if ($employee === null) {
            return [];
        }

        return app(AcknowledgmentOverviewService::class)->documentStatusesFor((int) $employee->id);
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.kenntnisnahme')
            ->layout('components.layouts.app', [
                'title' => 'Kenntnisnahme',
            ]);
    }

    /**
     * @return Builder<User>
     */
    protected function teamMembersQuery(): Builder
    {
        $scope = $this->leadership['scopeChoice']
            ? $this->teamScope
            : AcknowledgmentOverviewService::SCOPE_DIRECT;

        return app(AcknowledgmentOverviewService::class)->teamMembers((int) auth()->id(), $scope);
    }
}
