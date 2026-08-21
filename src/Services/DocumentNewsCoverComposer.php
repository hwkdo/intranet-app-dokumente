<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Models\DocumentNewsFrame;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Imagick;
use ImagickException;
use RuntimeException;

class DocumentNewsCoverComposer
{
    /**
     * @return array{
     *     width: int,
     *     height: int,
     *     slot_x: int,
     *     slot_y: int,
     *     slot_width: int,
     *     slot_height: int,
     *     thumb_max_width: int,
     *     thumb_max_height: int,
     *     default_filename: string
     * }
     */
    public function frameSpec(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('intranet-app-dokumente.news_frame', []);
        $settings = IntranetAppDokumenteSettings::resolvedAppSettings();

        return [
            'width' => (int) ($config['width'] ?? 1536),
            'height' => (int) ($config['height'] ?? 1024),
            'slot_x' => $settings->newsFrameSlotX ?? (int) ($config['slot_x'] ?? 513),
            'slot_y' => $settings->newsFrameSlotY ?? (int) ($config['slot_y'] ?? 295),
            'slot_width' => $settings->newsFrameSlotWidth ?? (int) ($config['slot_width'] ?? 540),
            'slot_height' => $settings->newsFrameSlotHeight ?? (int) ($config['slot_height'] ?? 600),
            'thumb_max_width' => (int) ($config['thumb_max_width'] ?? 320),
            'thumb_max_height' => (int) ($config['thumb_max_height'] ?? 420),
            'default_filename' => (string) ($config['default_filename'] ?? 'news-frame-default.png'),
        ];
    }

    public function defaultFramePath(): string
    {
        $filename = $this->frameSpec()['default_filename'];

        return dirname(__DIR__, 2).'/resources/images/'.$filename;
    }

    public function resolveFramePath(): string
    {
        $custom = DocumentNewsFrame::current()->customFramePath();
        if ($custom !== null) {
            return $custom;
        }

        $default = $this->defaultFramePath();
        if (! is_file($default)) {
            throw new RuntimeException('Default News-Rahmenbild fehlt: '.$default);
        }

        return $default;
    }

    /**
     * Fügt das Thumbnail mittig in die Rahmen-Lücke ein und speichert ein JPEG.
     *
     * @return string Absoluter Pfad zur temporären Datei
     */
    public function compose(string $thumbnailPath): string
    {
        if (! is_file($thumbnailPath)) {
            throw new RuntimeException('Thumbnail-Datei nicht gefunden: '.$thumbnailPath);
        }

        $spec = $this->frameSpec();
        $framePath = $this->resolveFramePath();

        try {
            $frame = new Imagick($framePath);
            $thumb = new Imagick($thumbnailPath);
        } catch (ImagickException $e) {
            throw new RuntimeException('Bild konnte nicht geladen werden: '.$e->getMessage(), 0, $e);
        }

        $frameWidth = $frame->getImageWidth();
        $frameHeight = $frame->getImageHeight();
        $scaleX = $frameWidth / max(1, $spec['width']);
        $scaleY = $frameHeight / max(1, $spec['height']);

        $slotX = (int) round($spec['slot_x'] * $scaleX);
        $slotY = (int) round($spec['slot_y'] * $scaleY);
        $slotW = (int) round($spec['slot_width'] * $scaleX);
        $slotH = (int) round($spec['slot_height'] * $scaleY);

        // Thumbnail auf Slot-Größe hochskalieren (Contain), damit DIN-A4 die Lücke weitgehend füllt.
        $thumb->setImageBackgroundColor('white');
        $thumb->resizeImage(max(1, $slotW), max(1, $slotH), Imagick::FILTER_LANCZOS, 1, true);
        $tw = $thumb->getImageWidth();
        $th = $thumb->getImageHeight();

        $x = $slotX + (int) round(($slotW - $tw) / 2);
        $y = $slotY + (int) round(($slotH - $th) / 2);

        $frame->compositeImage($thumb, Imagick::COMPOSITE_OVER, $x, $y);
        $frame->setImageFormat('jpeg');
        $frame->setImageCompressionQuality(88);

        $tmp = tempnam(sys_get_temp_dir(), 'dokumente-news-cover-');
        if ($tmp === false) {
            throw new RuntimeException('Temporäre Datei konnte nicht erstellt werden.');
        }
        $out = $tmp.'.jpg';
        @unlink($tmp);
        $frame->writeImage($out);

        $thumb->clear();
        $frame->clear();

        return $out;
    }

    public function ensureDefaultSeeded(): void
    {
        $record = DocumentNewsFrame::current();
        if ($record->hasCustomFrame()) {
            return;
        }

        $default = $this->defaultFramePath();
        if (! is_file($default)) {
            return;
        }

        $record->addMedia($default)
            ->preservingOriginal()
            ->usingFileName($this->frameSpec()['default_filename'])
            ->toMediaCollection('frame');
    }
}
