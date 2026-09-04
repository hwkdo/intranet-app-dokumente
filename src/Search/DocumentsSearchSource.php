<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Search;

use Hwkdo\IntranetAppBase\Data\SearchResult;
use Hwkdo\IntranetAppBase\Interfaces\SearchSourceInterface;
use Hwkdo\IntranetAppDokumente\IntranetAppDokumente;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class DocumentsSearchSource implements SearchSourceInterface
{
    public function key(): string
    {
        return 'dokumente.documents';
    }

    public function label(): string
    {
        return 'Dokumente';
    }

    public function appIdentifier(): string
    {
        return IntranetAppDokumente::identifier();
    }

    public function appName(): string
    {
        return IntranetAppDokumente::app_name();
    }

    public function icon(): string
    {
        return IntranetAppDokumente::app_icon();
    }

    public function isAvailableFor(Authenticatable $user): bool
    {
        if (! method_exists($user, 'can')) {
            return true;
        }

        return $user->can('see-app-'.$this->appIdentifier());
    }

    public function search(string $query, Authenticatable $user, int $limit): Collection
    {
        return DocumentSearch::query($query, $limit)
            ->map(fn (Document $document): SearchResult => new SearchResult(
                title: $document->title,
                url: route('apps.dokumente.show', $document),
                appIdentifier: $this->appIdentifier(),
                appName: $this->appName(),
                icon: $this->icon(),
                favoriteKey: $this->key().':'.$document->id,
                subtitle: $document->category?->name,
                sourceKey: $this->key(),
            ))
            ->values();
    }

    public function resolveFavorite(string $entityId, Authenticatable $user): ?SearchResult
    {
        if (! $this->isAvailableFor($user)) {
            return null;
        }

        $document = Document::query()->with('category')->find($entityId);

        if ($document === null) {
            return null;
        }

        return new SearchResult(
            title: $document->title,
            url: route('apps.dokumente.show', $document),
            appIdentifier: $this->appIdentifier(),
            appName: $this->appName(),
            icon: $this->icon(),
            favoriteKey: $this->key().':'.$document->id,
            subtitle: $document->category?->name,
            sourceKey: $this->key(),
        );
    }
}
