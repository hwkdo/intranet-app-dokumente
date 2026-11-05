<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DocumentAcknowledgment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'intranet_app_dokumente_document_acknowledgments';

    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('signed_pdf')
            ->singleFile()
            ->useDisk('local');
    }

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('intranet-app-dokumente.user_model', User::class), 'user_id');
    }
}
