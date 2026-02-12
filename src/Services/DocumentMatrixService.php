<?php

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\Gvp;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DocumentMatrixService
{
    public const CACHE_KEY_COUNT_MATRIX = 'intranet_app_dokumente.count_matrix';

    public static function clearCountMatrixCache(): void
    {
        Cache::forget(self::CACHE_KEY_COUNT_MATRIX);
    }

    /**
     * Basis-Query für gültige Dokumente (aktiv, gueltig_bis >= heute oder null).
     *
     * @return \Illuminate\Database\Eloquent\Builder<Document>
     */
    public function gueltigeDocumentsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Document::query()
            ->where('aktiv', true)
            ->where(function ($q) {
                $q->whereNull('gueltig_bis')
                    ->orWhere('gueltig_bis', '>=', today());
            });
    }

    /**
     * GVP-Struktur für die Matrix: HGF, Stäbe, GBs mit Kindern.
     *
     * @return array{hgf: Gvp|null, stabs: Collection, gbs: Collection}
     */
    public function getGvpStructure(): array
    {
        $hgf = Gvp::where('kuerzel', 'HGF')->first();
        if (! $hgf) {
            return [
                'hgf' => null,
                'stabs' => collect(),
                'gbs' => collect(),
            ];
        }

        $stabs = Gvp::where('kuerzel', 'Stab')
            ->where('parent_id', $hgf->id)
            ->orderBy('nummer')
            ->get();

        $gbs = Gvp::where('kuerzel', 'GB')
            ->where('parent_id', $hgf->id)
            ->with(['childGvps' => fn ($q) => $q->orderBy('nummer')])
            ->orderBy('nummer')
            ->get();

        return [
            'hgf' => $hgf,
            'stabs' => $stabs,
            'gbs' => $gbs,
        ];
    }

    /**
     * IDs aller Stab-GVPs (Kinder des HGF mit kuerzel Stab).
     *
     * @return array<int>
     */
    public function getStabGvpIds(): array
    {
        $hgf = Gvp::where('kuerzel', 'HGF')->first();
        if (! $hgf) {
            return [];
        }

        return Gvp::where('kuerzel', 'Stab')
            ->where('parent_id', $hgf->id)
            ->pluck('id')
            ->all();
    }

    /**
     * Dokumente für eine Matrix-Zelle: Kategorie + GVP (optional rekursiv).
     *
     * @param  int|null  $categoryId  null = alle Kategorien
     * @param  array<int>  $gvpIds  GVP-IDs (z. B. eine ID oder [id, ...descendantIds])
     * @return Collection<int, Document>
     */
    public function getDocumentsForCell(?int $categoryId, array $gvpIds): Collection
    {
        if ($gvpIds === []) {
            return collect();
        }

        $query = $this->gueltigeDocumentsQuery()
            ->whereIn('gvp_id', $gvpIds);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('title')->get();
    }

    /**
     * Dokumente für eine GVP-Zelle (rekursiv: GVP + alle Unter-GVPs).
     *
     * @param  int|null  $categoryId  null = alle Kategorien
     * @return Collection<int, Document>
     */
    public function getDocumentsForGvpRecursive(?int $categoryId, int $gvpId): Collection
    {
        $gvp = Gvp::find($gvpId);
        if (! $gvp) {
            return collect();
        }
        $ids = $gvp->getDescendantIds();

        return $this->getDocumentsForCell($categoryId, $ids);
    }

    /**
     * Dokumente für eine GVP-Zelle (nicht rekursiv, nur exakt diese GVP).
     *
     * @param  int|null  $categoryId  null = alle Kategorien
     * @return Collection<int, Document>
     */
    public function getDocumentsForGvpDirect(?int $categoryId, int $gvpId): Collection
    {
        return $this->getDocumentsForCell($categoryId, [$gvpId]);
    }

    /**
     * Dokumente für die Stab-Zeile (alle Stab-GVPs).
     *
     * @param  int|null  $categoryId  null = alle Kategorien
     * @return Collection<int, Document>
     */
    public function getDocumentsForStab(?int $categoryId): Collection
    {
        $stabIds = $this->getStabGvpIds();

        return $this->getDocumentsForCell($categoryId, $stabIds);
    }

    /**
     * Alle gültigen Dokumente (für „Alle X Dokumente“).
     *
     * @return Collection<int, Document>
     */
    public function getAllGueltigeDocuments(): Collection
    {
        return $this->gueltigeDocumentsQuery()->orderBy('title')->get();
    }

    /**
     * Dokumente nur nach Kategorie (für Summen-Zeile pro Kategorie).
     *
     * @return Collection<int, Document>
     */
    public function getDocumentsByCategory(?int $categoryId): Collection
    {
        $query = $this->gueltigeDocumentsQuery();
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('title')->get();
    }

    /**
     * Matrix-Kategorien (sortiert).
     *
     * @return Collection<int, DocumentCategory>
     */
    public function getCategories(): Collection
    {
        return DocumentCategory::orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Ermittelt für eine GVP-ID (inkl. Gruppe/Fachbereich) den zugehörigen GB und die Abteilung
     * durch Traversierung der Elternkette. Dokumente von G/FB werden so der übergeordneten Abteilung zugeordnet.
     *
     * @param  \Illuminate\Support\Collection<int, Gvp>  $gvpsById  einmalig geladene GVPs keyed by id
     * @return array{stab: bool, stab_id: int|null, gb: int|null, abt: int|null}|null null wenn nicht unter HGF
     */
    public function getMatrixLocationForGvp(int $gvpId, int $hgfId, Collection $gvpsById): ?array
    {
        $gvp = $gvpsById->get($gvpId);
        if (! $gvp) {
            return null;
        }

        $path = [];
        $current = $gvp;

        while ($current) {
            $path[] = $current;
            $current = $current->parent_id ? $gvpsById->get($current->parent_id) : null;
        }

        foreach ($path as $i => $node) {
            if ((int) $node->parent_id === (int) $hgfId) {
                if ($node->kuerzel === 'Stab') {
                    return ['stab' => true, 'stab_id' => (int) $node->id, 'gb' => null, 'abt' => null];
                }
                if ($node->kuerzel === 'GB') {
                    $abtId = $i > 0 ? (int) $path[$i - 1]->id : null;

                    return ['stab' => false, 'stab_id' => null, 'gb' => (int) $node->id, 'abt' => $abtId];
                }
                break;
            }
        }

        return null;
    }

    /**
     * Zähler-Matrix für die gesamte Tabelle in einer Abfrage (gecacht).
     * Keys: 'hgf', 'stab', 'gb' => [gbId => counts], 'gbDirect' => [gbId => counts], 'abt' => [abtId => counts], 'category' => [catId => count], 'all'.
     *
     * @return array<string, mixed>
     */
    public function getCountMatrix(): array
    {
        $ttl = config('intranet-app-dokumente.matrix_cache_ttl', 0);
        if ($ttl > 0) {
            return Cache::remember(self::CACHE_KEY_COUNT_MATRIX, $ttl, fn (): array => $this->computeCountMatrix());
        }

        return $this->computeCountMatrix();
    }

    /**
     * Berechnet die Zähler-Matrix per Elternkette: Jede Dokument-GVP (inkl. Gruppe/FB) wird
     * dem zugehörigen GB und der übergeordneten Abteilung zugeordnet.
     *
     * @return array<string, mixed>
     */
    protected function computeCountMatrix(): array
    {
        $structure = $this->getGvpStructure();
        $hgf = $structure['hgf'];
        $stabs = $structure['stabs'];
        $gbs = $structure['gbs'];

        $rows = $this->gueltigeDocumentsQuery()
            ->select('gvp_id', 'category_id')
            ->get();

        $hgfId = $hgf ? (int) $hgf->id : 0;
        $stabIds = $this->getStabGvpIds();
        $gbIds = $gbs->pluck('id')->map(fn ($id) => (int) $id)->all();
        $abtIds = $gbs->flatMap(fn ($gb) => $gb->childGvps->pluck('id'))->map(fn ($id) => (int) $id)->all();

        $matrix = [
            'hgf' => ['all' => 0],
            'stab' => ['all' => 0],
            'stabDirect' => array_fill_keys(array_map('intval', $stabIds), ['all' => 0]),
            'gb' => array_fill_keys($gbIds, ['all' => 0]),
            'gbDirect' => array_fill_keys($gbIds, ['all' => 0]),
            'abt' => array_fill_keys($abtIds, ['all' => 0]),
            'category' => [],
            'all' => 0,
        ];

        $gvpsById = Gvp::all()->keyBy('id');
        $locationByGvpId = [];
        foreach ($rows->pluck('gvp_id')->unique() as $gid) {
            $locationByGvpId[(int) $gid] = $this->getMatrixLocationForGvp((int) $gid, $hgfId, $gvpsById);
        }

        $addCount = function (array &$cell, int $categoryId): void {
            $cell['all'] = ($cell['all'] ?? 0) + 1;
            $cell[$categoryId] = ($cell[$categoryId] ?? 0) + 1;
        };

        foreach ($rows as $row) {
            $gvpId = (int) $row->gvp_id;
            $catId = (int) $row->category_id;

            $matrix['all']++;
            $matrix['category'][$catId] = ($matrix['category'][$catId] ?? 0) + 1;

            if ($hgfId === 0) {
                continue;
            }

            $loc = $locationByGvpId[$gvpId] ?? null;
            if (! $loc) {
                continue;
            }

            $addCount($matrix['hgf'], $catId);

            if ($loc['stab']) {
                $addCount($matrix['stab'], $catId);
                if (isset($matrix['stabDirect'][$loc['stab_id']])) {
                    $addCount($matrix['stabDirect'][$loc['stab_id']], $catId);
                }

                continue;
            }

            if ($loc['gb'] !== null && isset($matrix['gb'][$loc['gb']])) {
                $addCount($matrix['gb'][$loc['gb']], $catId);
                if ($loc['abt'] === null) {
                    $addCount($matrix['gbDirect'][$loc['gb']], $catId);
                } elseif (isset($matrix['abt'][$loc['abt']])) {
                    $addCount($matrix['abt'][$loc['abt']], $catId);
                }
            }
        }

        return $matrix;
    }
}
