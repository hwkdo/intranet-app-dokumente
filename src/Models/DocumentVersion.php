<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentVersion extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'intranet_app_dokumente_document_versions';

    protected $guarded = [];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(config('intranet-app-dokumente.user_model', User::class), 'uploader_id');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(DocumentAcknowledgment::class, 'document_version_id');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Wie intranet-app-mein-arbeitsschutz: Contain-Thumb, synchron.
        // PDF-Rasterung läuft über PdftoppmPdfImageGenerator (ImageMagick-PDF-Policy).
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 320, 420)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->singleFile();
    }
}
