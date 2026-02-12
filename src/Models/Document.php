<?php

namespace Hwkdo\IntranetAppDokumente\Models;

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\IntranetAppDokumente\Services\DocumentMatrixService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Document extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'intranet_app_dokumente_documents';

    protected $guarded = [];

    protected static function booted(): void
    {
        $clearCache = function (): void {
            DocumentMatrixService::clearCountMatrixCache();
        };
        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
    }

    protected function casts(): array
    {
        return [
            'aktiv' => 'boolean',
            'gueltig_bis' => 'date',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function gvp(): BelongsTo
    {
        return $this->belongsTo(Gvp::class, 'gvp_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->singleFile();
    }

    public function isGueltig(): bool
    {
        if (! $this->aktiv) {
            return false;
        }
        if ($this->gueltig_bis === null) {
            return true;
        }

        return $this->gueltig_bis->gte(today());
    }
}
