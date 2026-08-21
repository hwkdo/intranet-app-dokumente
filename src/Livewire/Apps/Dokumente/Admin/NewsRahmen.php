<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Admin;

use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Data\AppSettings;
use Hwkdo\IntranetAppDokumente\Models\DocumentNewsFrame;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Hwkdo\IntranetAppDokumente\Services\DocumentNewsCoverComposer;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class NewsRahmen extends Component
{
    use WithFileUploads;

    public $frameUpload = null;

    public int $slotX = 513;

    public int $slotY = 295;

    public int $slotWidth = 540;

    public int $slotHeight = 600;

    public function mount(DocumentNewsCoverComposer $composer): void
    {
        $this->authorize('manage-app-dokumente');
        $composer->ensureDefaultSeeded();

        $spec = $composer->frameSpec();
        $this->slotX = $spec['slot_x'];
        $this->slotY = $spec['slot_y'];
        $this->slotWidth = $spec['slot_width'];
        $this->slotHeight = $spec['slot_height'];
    }

    /**
     * @return array<string, int|string>
     */
    public function getFrameSpecProperty(): array
    {
        return app(DocumentNewsCoverComposer::class)->frameSpec();
    }

    public function getCurrentFrameUrlProperty(): ?string
    {
        return DocumentNewsFrame::current()->customFrameUrl();
    }

    public function saveFrame(): void
    {
        $this->validate([
            'frameUpload' => ['required', 'image', 'max:10240'],
        ]);

        $record = DocumentNewsFrame::current();
        $record->addMedia($this->frameUpload->getRealPath())
            ->usingFileName(
                pathinfo($this->frameUpload->getClientOriginalName(), PATHINFO_FILENAME)
                .'.'.$this->frameUpload->getClientOriginalExtension()
            )
            ->toMediaCollection('frame');

        $this->frameUpload = null;

        Flux::toast(
            heading: 'Gespeichert',
            text: 'News-Rahmenbild wurde aktualisiert.',
            variant: 'success',
        );
    }

    public function resetToDefault(DocumentNewsCoverComposer $composer): void
    {
        $record = DocumentNewsFrame::current();
        $record->clearMediaCollection('frame');

        $default = $composer->defaultFramePath();
        if (is_file($default)) {
            $record->addMedia($default)
                ->preservingOriginal()
                ->usingFileName($composer->frameSpec()['default_filename'])
                ->toMediaCollection('frame');
        }

        $config = config('intranet-app-dokumente.news_frame', []);
        $this->slotX = (int) ($config['slot_x'] ?? 513);
        $this->slotY = (int) ($config['slot_y'] ?? 295);
        $this->slotWidth = (int) ($config['slot_width'] ?? 540);
        $this->slotHeight = (int) ($config['slot_height'] ?? 600);

        $current = IntranetAppDokumenteSettings::resolvedAppSettings();
        IntranetAppDokumenteSettings::persistAppSettings(AppSettings::from(array_merge($current->toArray(), [
            'newsFrameSlotX' => $this->slotX,
            'newsFrameSlotY' => $this->slotY,
            'newsFrameSlotWidth' => $this->slotWidth,
            'newsFrameSlotHeight' => $this->slotHeight,
        ])));

        Flux::toast(
            heading: 'Zurückgesetzt',
            text: 'Standard-Rahmenbild und Slot-Maße wurden wiederhergestellt.',
            variant: 'success',
        );
    }

    public function saveSlotSettings(): void
    {
        $this->validate([
            'slotX' => ['required', 'integer', 'min:0'],
            'slotY' => ['required', 'integer', 'min:0'],
            'slotWidth' => ['required', 'integer', 'min:1'],
            'slotHeight' => ['required', 'integer', 'min:1'],
        ]);

        $current = IntranetAppDokumenteSettings::resolvedAppSettings();
        $settings = AppSettings::from(array_merge($current->toArray(), [
            'newsFrameSlotX' => $this->slotX,
            'newsFrameSlotY' => $this->slotY,
            'newsFrameSlotWidth' => $this->slotWidth,
            'newsFrameSlotHeight' => $this->slotHeight,
        ]));

        IntranetAppDokumenteSettings::persistAppSettings($settings);

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Lücken-Koordinaten wurden gespeichert.',
            variant: 'success',
        );
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.admin.news-rahmen');
    }
}
