<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use App\Models\User;
use Hwkdo\IntranetAppDokumente\Enums\FormwerkRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFormwerkRequest extends Model
{
    protected $table = 'intranet_app_dokumente_formwerk_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => FormwerkRequestStatus::class,
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
