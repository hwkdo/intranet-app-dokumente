<?php

namespace Hwkdo\IntranetAppDokumente\Models;

use Hwkdo\IntranetAppDokumente\Services\DocumentMatrixService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    protected $table = 'intranet_app_dokumente_categories';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleted(function (): void {
            DocumentMatrixService::clearCountMatrixCache();
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }
}
