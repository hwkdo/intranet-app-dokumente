<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\IntranetAppDokumente\Database\Factories\DocumentFactory;
use Hwkdo\IntranetAppDokumente\Services\DocumentMatrixService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    protected $table = 'intranet_app_dokumente_documents';

    protected $guarded = [];

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    protected static function booted(): void
    {
        $clearCache = function (): void {
            DocumentMatrixService::clearCountMatrixCache();
        };
        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
        static::restored($clearCache);
    }

    protected function casts(): array
    {
        return [
            'aktiv' => 'boolean',
            'requires_acknowledgment' => 'boolean',
            'is_onboarding_it' => 'boolean',
            'is_onboarding_perso' => 'boolean',
            'gueltig_bis' => 'date',
            'last_review_notified_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(config('intranet-app-dokumente.user_model', User::class), 'uploader_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(config('intranet-app-dokumente.user_model', User::class), 'responsible_id');
    }

    public function gvp(): BelongsTo
    {
        return $this->belongsTo(Gvp::class, 'gvp_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DocumentHistory::class, 'document_id')->orderByDesc('created_at');
    }

    public function searchableAs(): string
    {
        return 'dokumente_documents';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $vorname = trim((string) ($this->uploader?->vorname ?? ''));
        $nachname = trim((string) ($this->uploader?->nachname ?? ''));

        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'category_name' => $this->category?->name ?? '',
            'uploader_display' => trim($vorname.' '.$nachname),
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->isGueltig() && ! $this->trashed();
    }

    public function typesenseSearchParameters(): array
    {
        return [
            'infix' => 'always',
        ];
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['category', 'uploader']);
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

    public function reviewDueAt(): Carbon
    {
        if ($this->gueltig_bis !== null) {
            return $this->gueltig_bis->copy()->startOfDay();
        }

        return $this->created_at?->copy()->addYear()->startOfDay() ?? now()->addYear()->startOfDay();
    }

    public function reviewWarningStartsAt(int $warningDays): Carbon
    {
        return $this->reviewDueAt()->copy()->subDays(max(0, $warningDays))->startOfDay();
    }

    public function isInReviewWindow(int $warningDays): bool
    {
        if (! $this->aktiv || $this->trashed()) {
            return false;
        }

        return today()->gte($this->reviewWarningStartsAt($warningDays));
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeGueltig(Builder $query): Builder
    {
        return $query
            ->where('aktiv', true)
            ->where(function (Builder $q): void {
                $q->whereNull('gueltig_bis')
                    ->orWhere('gueltig_bis', '>=', today());
            });
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopePendingAcknowledgmentFor(Builder $query, int $userId): Builder
    {
        return $query
            ->where('requires_acknowledgment', true)
            ->whereNotNull('current_version_id')
            ->whereDoesntHave('currentVersion.acknowledgments', function (Builder $q) use ($userId): void {
                $q->where('user_id', $userId);
            });
    }
}
