<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentAcknowledgment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AcknowledgmentOverviewService
{
    public const SCOPE_DIRECT = 'direct';

    public const SCOPE_ALL = 'all';

    public function pendingCountFor(int $userId): int
    {
        if ($userId < 1) {
            return 0;
        }

        return Document::query()
            ->requiringCurrentAcknowledgment()
            ->whereDoesntHave('currentVersion.acknowledgments', function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->count();
    }

    /**
     * Leitungseinheiten des Nutzers (Vorgesetzte und Stellvertretung).
     *
     * @return array{led: Collection<int, Gvp>, canViewTeam: bool, scopeChoice: bool, label: string}
     */
    public function leadership(int $userId): array
    {
        $led = Gvp::query()
            ->where(function (Builder $query) use ($userId): void {
                $query->where('vorgesetzter_id', $userId)
                    ->orWhere('stellvertreter_id', $userId);
            })
            ->orderBy('kuerzel')
            ->orderBy('nummer')
            ->get();

        $ids = $led->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        return [
            'led' => $led,
            'canViewTeam' => $led->isNotEmpty(),
            'scopeChoice' => $ids !== [] && Gvp::query()->whereIn('parent_id', $ids)->exists(),
            'label' => $led
                ->map(fn (Gvp $gvp): string => trim($gvp->kuerzel.' '.$gvp->nummer.' '.$gvp->name))
                ->join(', '),
        ];
    }

    /**
     * @return list<int>
     */
    public function teamGvpIds(int $userId, string $scope): array
    {
        $leadership = $this->leadership($userId);
        $ledIds = $leadership['led']
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($ledIds === [] || $scope !== self::SCOPE_ALL || ! $leadership['scopeChoice']) {
            return $ledIds;
        }

        return $this->withDescendants($ledIds);
    }

    /**
     * @return Builder<User>
     */
    public function teamMembers(int $viewerId, string $scope): Builder
    {
        $gvpIds = $this->teamGvpIds($viewerId, $scope);
        $userClass = config('intranet-app-dokumente.user_model', User::class);

        $query = $userClass::query()
            ->aktiv()
            ->whereKeyNot($viewerId);

        if ($gvpIds === []) {
            return $query->whereIn('id', []);
        }

        return $query
            ->where(function (Builder $inner) use ($gvpIds): void {
                $inner->whereIn('gvp_id', $gvpIds)
                    ->orWhereHas('gvpSecondary', function (Builder $secondary) use ($gvpIds): void {
                        $secondary->whereIn('gvps.id', $gvpIds);
                    });
            })
            ->permission('see-app-dokumente')
            ->orderBy('nachname')
            ->orderBy('vorname');
    }

    /**
     * @param  iterable<int, User>  $users
     * @return array<int, array{open: int, done: int}>
     */
    public function statusCounts(iterable $users): array
    {
        $userIds = collect($users)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        $versionIds = Document::query()
            ->requiringCurrentAcknowledgment()
            ->pluck('current_version_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $total = count($versionIds);

        $result = [];
        foreach ($userIds as $id) {
            $result[$id] = ['open' => $total, 'done' => 0];
        }

        if ($userIds === [] || $versionIds === []) {
            return $result;
        }

        $rows = DocumentAcknowledgment::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('document_version_id', $versionIds)
            ->get(['user_id', 'document_version_id']);

        $doneByUser = [];
        foreach ($rows as $row) {
            $doneByUser[(int) $row->user_id][(int) $row->document_version_id] = true;
        }

        foreach ($userIds as $id) {
            $done = isset($doneByUser[$id]) ? count($doneByUser[$id]) : 0;
            $result[$id] = [
                'open' => max(0, $total - $done),
                'done' => $done,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{document: Document, acknowledged: bool, acknowledged_at: ?Carbon}>
     */
    public function documentStatusesFor(int $userId): array
    {
        $documents = Document::query()
            ->requiringCurrentAcknowledgment()
            ->with(['category', 'currentVersion'])
            ->orderBy('title')
            ->get();

        $versionIds = $documents->pluck('current_version_id')->map(fn (mixed $id): int => (int) $id)->all();
        $acknowledgments = $versionIds === []
            ? collect()
            : DocumentAcknowledgment::query()
                ->where('user_id', $userId)
                ->whereIn('document_version_id', $versionIds)
                ->get()
                ->keyBy('document_version_id');

        return $documents->map(function (Document $document) use ($acknowledgments): array {
            $acknowledgment = $acknowledgments->get($document->current_version_id);

            return [
                'document' => $document,
                'acknowledged' => $acknowledgment !== null,
                'acknowledged_at' => $acknowledgment?->acknowledged_at,
            ];
        })->all();
    }

    /**
     * @param  list<int>  $rootIds
     * @return list<int>
     */
    private function withDescendants(array $rootIds): array
    {
        $childrenByParent = Gvp::query()
            ->get(['id', 'parent_id'])
            ->groupBy(fn (Gvp $gvp): int => (int) $gvp->parent_id);
        $ids = $rootIds;
        $queue = $rootIds;

        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($childrenByParent->get($current, collect()) as $child) {
                $childId = (int) $child->id;
                if (in_array($childId, $ids, true)) {
                    continue;
                }

                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }
}
