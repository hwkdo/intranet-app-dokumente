<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Tasks;

use Hwkdo\IntranetAppBase\Data\TaskItem;
use Hwkdo\IntranetAppBase\Interfaces\TaskProviderInterface;
use Hwkdo\IntranetAppDokumente\IntranetAppDokumente;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class PendingAcknowledgmentTaskProvider implements TaskProviderInterface
{
    public function getLabel(): string
    {
        return 'Dokumente zur Kenntnisnahme';
    }

    public function getTasksForUser(Authenticatable $user): Collection
    {
        $userId = method_exists($user, 'getKey') ? (int) $user->getKey() : 0;
        if ($userId < 1 || ! method_exists($user, 'can') || ! $user->can('see-app-dokumente')) {
            return collect();
        }

        return Document::query()
            ->pendingAcknowledgmentFor($userId)
            ->with(['currentVersion'])
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(fn (Document $document): TaskItem => new TaskItem(
                title: $document->title,
                url: route('apps.dokumente.index', ['document' => $document->id]),
                appIdentifier: IntranetAppDokumente::identifier(),
                appName: IntranetAppDokumente::app_name(),
                appIcon: IntranetAppDokumente::app_icon(),
                description: 'Dokument öffnen, herunterladen und zur Kenntnis nehmen',
                badge: 'Kenntnisnahme',
                priority: 80,
            ))
            ->values();
    }
}
