<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\User;
use Hwkdo\IntranetAppDokumente\Enums\AcknowledgmentReportStatusFilter;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentAcknowledgment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentAcknowledgmentReportService
{
    /**
     * @return array{total: int, acknowledged: int, pending: int}
     */
    public function counts(Document $document): array
    {
        $version = $document->currentVersion;
        if (! $document->requires_acknowledgment || $version === null) {
            return ['total' => 0, 'acknowledged' => 0, 'pending' => 0];
        }

        $userClass = $this->userClass();
        $audienceIds = $userClass::permission('see-app-dokumente')->pluck('id');
        $total = $audienceIds->count();

        if ($total === 0) {
            return ['total' => 0, 'acknowledged' => 0, 'pending' => 0];
        }

        $acknowledged = DocumentAcknowledgment::query()
            ->where('document_version_id', $version->id)
            ->whereIn('user_id', $audienceIds)
            ->count();

        return [
            'total' => $total,
            'acknowledged' => $acknowledged,
            'pending' => max(0, $total - $acknowledged),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginate(
        Document $document,
        AcknowledgmentReportStatusFilter $status = AcknowledgmentReportStatusFilter::All,
        string $search = '',
        int $perPage = 25,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $paginator = $this->baseQuery($document, $status, $search)
            ->paginate(perPage: $perPage, pageName: $pageName);

        $acks = $this->acknowledgmentsByUserId($document, $paginator->getCollection()->pluck('id'));

        return $paginator->through(fn ($user) => $this->mapUser($user, $acks->get($user->id)));
    }

    /**
     * @return Collection<int, object>
     */
    public function collect(
        Document $document,
        AcknowledgmentReportStatusFilter $status = AcknowledgmentReportStatusFilter::All,
        string $search = '',
    ): Collection {
        $users = $this->baseQuery($document, $status, $search)->get();
        $acks = $this->acknowledgmentsByUserId($document, $users->pluck('id'));

        return $users->map(fn ($user) => $this->mapUser($user, $acks->get($user->id)));
    }

    /**
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function baseQuery(
        Document $document,
        AcknowledgmentReportStatusFilter $status,
        string $search,
    ): Builder {
        $version = $document->currentVersion;
        $userClass = $this->userClass();
        $usersTable = (new $userClass)->getTable();

        if (! $document->requires_acknowledgment || $version === null) {
            return $userClass::query()->whereRaw('1 = 0');
        }

        $acknowledgedUserIds = DocumentAcknowledgment::query()
            ->where('document_version_id', $version->id)
            ->pluck('user_id');

        $query = $userClass::permission('see-app-dokumente');

        $search = trim($search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($usersTable, $like): void {
                $q->where("{$usersTable}.vorname", 'like', $like)
                    ->orWhere("{$usersTable}.nachname", 'like', $like);
            });
        }

        match ($status) {
            AcknowledgmentReportStatusFilter::Acknowledged => $query->whereIn("{$usersTable}.id", $acknowledgedUserIds),
            AcknowledgmentReportStatusFilter::Pending => $query->whereNotIn("{$usersTable}.id", $acknowledgedUserIds),
            AcknowledgmentReportStatusFilter::All => null,
        };

        return $query
            ->orderBy("{$usersTable}.nachname")
            ->orderBy("{$usersTable}.vorname");
    }

    /**
     * @param  Collection<int, int|string>  $userIds
     * @return Collection<int, DocumentAcknowledgment>
     */
    protected function acknowledgmentsByUserId(Document $document, Collection $userIds): Collection
    {
        $version = $document->currentVersion;
        if ($version === null || $userIds->isEmpty()) {
            return collect();
        }

        return DocumentAcknowledgment::query()
            ->where('document_version_id', $version->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');
    }

    protected function mapUser(object $user, ?DocumentAcknowledgment $acknowledgment): object
    {
        $acknowledgedAt = $acknowledgment?->acknowledged_at;

        return (object) [
            'user_id' => (int) $user->id,
            'vorname' => (string) ($user->vorname ?? ''),
            'nachname' => (string) ($user->nachname ?? ''),
            'email' => (string) ($user->email ?? ''),
            'acknowledged' => $acknowledgment !== null,
            'acknowledged_at' => $acknowledgedAt,
            'confirmation_method' => $acknowledgment?->confirmation_method,
            'status_label' => $acknowledgment !== null ? 'Erfolgt' : 'Nicht erfolgt',
        ];
    }

    /**
     * @return class-string
     */
    protected function userClass(): string
    {
        return config('intranet-app-dokumente.user_model', User::class);
    }
}
