<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Tasks;

use Hwkdo\IntranetAppBase\Data\TaskItem;
use Hwkdo\IntranetAppBase\Interfaces\TaskProviderInterface;
use Hwkdo\IntranetAppDokumente\Data\AppSettings;
use Hwkdo\IntranetAppDokumente\IntranetAppDokumente;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentReviewTaskProvider implements TaskProviderInterface
{
    public function getLabel(): string
    {
        return 'Dokumente zur Prüfung';
    }

    public function getTasksForUser(Authenticatable $user): Collection
    {
        $userId = method_exists($user, 'getKey') ? (int) $user->getKey() : 0;
        if ($userId < 1) {
            return collect();
        }

        $warningDays = $this->warningDays();

        return Document::query()
            ->where('aktiv', true)
            ->where(function (Builder $q) use ($userId): void {
                $q->where('responsible_id', $userId)
                    ->orWhere('uploader_id', $userId);
            })
            ->orderBy('title')
            ->limit(100)
            ->get()
            ->filter(fn (Document $document): bool => $document->isInReviewWindow($warningDays))
            ->take(50)
            ->map(fn (Document $document): TaskItem => new TaskItem(
                title: $document->title,
                url: route('apps.dokumente.review', $document),
                appIdentifier: IntranetAppDokumente::identifier(),
                appName: IntranetAppDokumente::app_name(),
                appIcon: IntranetAppDokumente::app_icon(),
                description: 'Gültigkeit prüfen: verlängern, aktualisieren oder löschen',
                badge: 'Prüfung',
                priority: 70,
            ))
            ->values();
    }

    protected function warningDays(): int
    {
        $row = IntranetAppDokumenteSettings::current();
        if ($row && $row->settings instanceof AppSettings) {
            return $row->settings->validityWarningDays;
        }

        return (new AppSettings)->validityWarningDays;
    }
}
