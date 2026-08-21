<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DocumentNewsFrame extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'intranet_app_dokumente_news_frames';

    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('frame')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function hasCustomFrame(): bool
    {
        return $this->hasMedia('frame');
    }

    public function customFrameUrl(): ?string
    {
        return $this->hasCustomFrame() ? $this->getFirstMediaUrl('frame') : null;
    }

    public function customFramePath(): ?string
    {
        if (! $this->hasCustomFrame()) {
            return null;
        }

        $path = $this->getFirstMediaPath('frame');

        return is_string($path) && is_file($path) ? $path : null;
    }
}
