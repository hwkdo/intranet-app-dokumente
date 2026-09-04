<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\Gvp;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Support\Collection;

class DocumentGvpResolver
{
    /**
     * Gespeicherte GVP fehlt oder ist im HGF-Baum nicht mehr erreichbar.
     */
    public function hasInvalidStoredGvp(Document $document): bool
    {
        if ($document->gvp_id === null) {
            return false;
        }

        $gvpsById = Gvp::query()->get()->keyBy('id');

        return $this->isStoredGvpInvalidInMap((int) $document->gvp_id, $gvpsById);
    }

    /**
     * Gültige GVP des Verantwortlichen (existiert und unter HGF erreichbar), sonst null.
     */
    public function responsibleFallbackGvp(Document $document): ?Gvp
    {
        $responsible = $document->relationLoaded('responsible')
            ? $document->responsible
            : $document->responsible()->with('gvp')->first();

        if ($responsible === null || $responsible->gvp_id === null) {
            return null;
        }

        $gvp = $responsible->relationLoaded('gvp')
            ? $responsible->gvp
            : Gvp::query()->find($responsible->gvp_id);

        if ($gvp === null) {
            return null;
        }

        $gvpsById = Gvp::query()->get()->keyBy('id');
        if ($this->isStoredGvpInvalidInMap((int) $gvp->id, $gvpsById)) {
            return null;
        }

        return $gvp;
    }

    public function effectiveGvpId(Document $document): ?int
    {
        if (! $this->hasInvalidStoredGvp($document)) {
            return $document->gvp_id !== null ? (int) $document->gvp_id : null;
        }

        return $this->responsibleFallbackGvp($document)?->id;
    }

    public function effectiveGvp(Document $document): ?Gvp
    {
        if (! $this->hasInvalidStoredGvp($document)) {
            return $document->relationLoaded('gvp')
                ? $document->gvp
                : $document->gvp()->first();
        }

        return $this->responsibleFallbackGvp($document);
    }

    /**
     * @param  Collection<int, Gvp>  $gvpsById
     * @param  Collection<int, int|null>  $responsibleGvpByUserId
     */
    public function effectiveGvpIdFromMaps(
        ?int $storedGvpId,
        ?int $responsibleId,
        Collection $gvpsById,
        Collection $responsibleGvpByUserId,
    ): ?int {
        if ($storedGvpId === null) {
            return null;
        }

        if (! $this->isStoredGvpInvalidInMap($storedGvpId, $gvpsById)) {
            return $storedGvpId;
        }

        if ($responsibleId === null) {
            return null;
        }

        $respGvpId = $responsibleGvpByUserId->get($responsibleId);
        if ($respGvpId === null) {
            return null;
        }

        $respGvpId = (int) $respGvpId;

        return $this->isStoredGvpInvalidInMap($respGvpId, $gvpsById) ? null : $respGvpId;
    }

    /**
     * @param  Collection<int, Gvp>  $gvpsById
     */
    public function isStoredGvpInvalidInMap(?int $storedGvpId, Collection $gvpsById): bool
    {
        if ($storedGvpId === null) {
            return false;
        }

        $stored = $gvpsById->get($storedGvpId);
        if ($stored === null) {
            return true;
        }

        return ! $this->isReachableUnderHgf($storedGvpId, $gvpsById);
    }

    /**
     * @param  Collection<int, Gvp>  $gvpsById
     */
    public function isReachableUnderHgf(int $gvpId, Collection $gvpsById): bool
    {
        $hgf = $gvpsById->first(
            fn (Gvp $gvp): bool => $gvp->kuerzel === 'HGF' && $gvp->parent_id === null
        );

        if ($hgf === null) {
            return false;
        }

        if ($gvpId === (int) $hgf->id) {
            return true;
        }

        return app(DocumentMatrixService::class)
            ->getMatrixLocationForGvp($gvpId, (int) $hgf->id, $gvpsById) !== null;
    }
}
