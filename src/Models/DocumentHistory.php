<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use App\Models\User;
use Hwkdo\IntranetAppDokumente\Enums\DocumentHistoryEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentHistory extends Model
{
    protected $table = 'intranet_app_dokumente_document_histories';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event' => DocumentHistoryEvent::class,
            'meta' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
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
