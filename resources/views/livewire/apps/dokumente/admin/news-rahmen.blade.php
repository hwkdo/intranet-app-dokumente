<div class="space-y-4">
    @php
        $spec = $this->frameSpec;
    @endphp

    <x-intranet-app-dokumente::hwk-card title="Vorgaben für ein neues Rahmenbild" icon="information-circle" description="Größe, Format und freie Fläche für das Dokument-Thumbnail." padded>
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-white/70">
            <li>Empfohlene Bildgröße: <strong class="text-[#073070] dark:text-white">{{ $spec['width'] }} × {{ $spec['height'] }} px</strong> (Querformat, wie News-/Slider-Titelbilder nach Skalierung).</li>
            <li>Formate: PNG, JPG oder WebP, max. 10&nbsp;MB.</li>
            <li>Mittig eine freie, möglichst gleichmäßige Fläche für das Dokument-Thumbnail lassen.</li>
            <li>Standard-Lücke (bezogen auf {{ $spec['width'] }}×{{ $spec['height'] }}):
                Position <strong class="text-[#073070] dark:text-white">X={{ $spec['slot_x'] }}, Y={{ $spec['slot_y'] }}</strong>,
                Größe <strong class="text-[#073070] dark:text-white">{{ $spec['slot_width'] }} × {{ $spec['slot_height'] }} px</strong>.
            </li>
            <li>Dokument-Thumbs (typisch DIN A4) werden in der Lücke <strong class="text-[#073070] dark:text-white">möglichst groß</strong> zentriert (Contain auf Slot-Größe).</li>
            <li>Weicht die Upload-Größe ab, werden die Lücken-Koordinaten proportional skaliert.</li>
        </ul>
    </x-intranet-app-dokumente::hwk-card>

    <x-intranet-app-dokumente::hwk-card title="Aktuelles Rahmenbild" icon="newspaper" description="Dieses Bild rahmt die Dokument-Vorschau in der News." padded>
        @if($this->currentFrameUrl)
            <div class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                <img src="{{ $this->currentFrameUrl }}" alt="News-Rahmen" class="max-h-80 w-full bg-[#d0e3f9]/30 object-contain dark:bg-[#04214e]" />
            </div>
        @else
            <x-intranet-app-dokumente::hwk-empty bare icon="photo" title="Kein Rahmenbild vorhanden">
                Laden Sie ein Rahmenbild hoch oder stellen Sie den Standard wieder her.
            </x-intranet-app-dokumente::hwk-empty>
        @endif

        <div class="mt-4 space-y-3">
            <flux:file-upload wire:model="frameUpload" label="Neues Rahmenbild hochladen">
                <flux:file-upload.dropzone
                    heading="Bild hierher ziehen oder auswählen"
                    text="PNG, JPG, WebP · max. 10 MB"
                    with-progress
                />
            </flux:file-upload>
            @error('frameUpload')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if($frameUpload)
                <flux:file-item :heading="$frameUpload->getClientOriginalName()" :size="$frameUpload->getSize()">
                    <x-slot:actions>
                        <flux:file-item.remove wire:click="$set('frameUpload', null)" />
                    </x-slot:actions>
                </flux:file-item>
                <flux:button wire:click="saveFrame" variant="primary">Rahmen speichern</flux:button>
            @endif

            <div class="flex flex-wrap gap-2">
                <flux:button
                    wire:click="resetToDefault"
                    wire:confirm="Rahmen und Lücken-Maße auf den Standard zurücksetzen?"
                    variant="ghost"
                >Standard wiederherstellen</flux:button>
            </div>
        </div>
    </x-intranet-app-dokumente::hwk-card>

    <x-intranet-app-dokumente::hwk-card title="Lücke für Dokument-Thumbnail" icon="squares-2x2" description="Koordinaten beziehen sich auf die Referenzgröße {{ $spec['width'] }}×{{ $spec['height'] }} px. Bei anders großem Rahmenbild werden sie automatisch proportional umgerechnet." padded>
        <form wire:submit="saveSlotSettings" class="grid gap-4 sm:grid-cols-2">
            <flux:input type="number" wire:model="slotX" label="Slot X" min="0" required />
            <flux:input type="number" wire:model="slotY" label="Slot Y" min="0" required />
            <flux:input type="number" wire:model="slotWidth" label="Slot-Breite" min="1" required />
            <flux:input type="number" wire:model="slotHeight" label="Slot-Höhe" min="1" required />
            <div class="sm:col-span-2">
                <flux:button type="submit" variant="primary">Lücken-Maße speichern</flux:button>
            </div>
        </form>
    </x-intranet-app-dokumente::hwk-card>
</div>
