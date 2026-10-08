<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLightRagState extends Model
{
    protected $table = 'intranet_app_dokumente_lightrag_states';

    protected $guarded = [];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    protected function casts(): array
    {
        return [
            'status' => DocumentLightRagStatus::class,
            'indexed_at' => 'datetime',
        ];
    }
}
