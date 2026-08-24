<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Search;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Support\Collection;

class DocumentSearch
{
    /**
     * @return Collection<int, Document>
     */
    public static function query(string $query, int $limit): Collection
    {
        return Document::search($query)
            ->query(fn ($builder) => $builder->gueltig()->with(['category', 'uploader']))
            ->take($limit)
            ->get();
    }
}
