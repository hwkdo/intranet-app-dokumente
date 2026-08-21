<div class="space-y-4">
    @php
        $spec = $this->frameSpec;
    @endphp

    <flux:card class="glass-card">
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.heading>Vorgaben für ein neues Rahmenbild</flux:callout.heading>
            <flux:callout.text>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    <li>Empfohlene Bildgröße: <strong>{{ $spec['width'] }} × {{ $spec['height'] }} px</strong> (Querformat, wie News-/Slider-Titelbilder nach Skalierung).</li>
                    <li>Formate: PNG, JPG oder WebP, max. 10&nbsp;MB.</li>
                    <li>Mittig eine freie, möglichst gleichmäßige Fläche für das Dokument-Thumbnail lassen.</li>
                    <li>Standard-Lücke (bezogen auf {{ $spec['width'] }}×{{ $spec['height'] }}):
                        Position <strong>X={{ $spec['slot_x'] }}, Y={{ $spec['slot_y'] }}</strong>,
                        Größe <strong>{{ $spec['slot_width'] }} × {{ $spec['slot_height'] }} px</strong>.
                    </li>
                    <li>Dokument-Thumbs (typisch DIN A4) werden in der Lücke <strong>möglichst groß</strong> zentriert (Contain auf Slot-Größe).</li>
                    <li>Weicht die Upload-Größe ab, werden die Lücken-Koordinaten proportional skaliert.</li>
                </ul>
            </flux:callout.text>
        </flux:callout>
    </flux:card>

    <flux:card class="glass-card space-y-4">
        <flux:heading size="lg">Aktuelles Rahmenbild</flux:heading>
        @if($this->currentFrameUrl)
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <img src="{{ $this->currentFrameUrl }}" alt="News-Rahmen" class="w-full max-h-80 object-contain bg-zinc-50 dark:bg-zinc-900" />
            </div>
        @else
            <flux:text>Kein Rahmenbild vorhanden.</flux:text>
        @endif

        <flux:file-upload wire:model="frameUpload" label="Neues Rahmenbild hochladen">
            <flux:file-upload.dropzone
                heading="Bild hierher ziehen oder auswählen"
                text="PNG, JPG, WebP · max. 10 MB"
                with-progress
            />
        </flux:file-upload>
        @error('frameUpload')
            <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
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
    </flux:card>

    <flux:card class="glass-card space-y-4">
        <flux:heading size="lg">Lücke für Dokument-Thumbnail</flux:heading>
        <flux:text>
            Koordinaten beziehen sich auf die Referenzgröße {{ $spec['width'] }}×{{ $spec['height'] }} px.
            Bei anders großem Rahmenbild werden sie automatisch proportional umgerechnet.
        </flux:text>
        <form wire:submit="saveSlotSettings" class="grid gap-4 sm:grid-cols-2">
            <flux:input type="number" wire:model="slotX" label="Slot X" min="0" required />
            <flux:input type="number" wire:model="slotY" label="Slot Y" min="0" required />
            <flux:input type="number" wire:model="slotWidth" label="Slot-Breite" min="1" required />
            <flux:input type="number" wire:model="slotHeight" label="Slot-Höhe" min="1" required />
            <div class="sm:col-span-2">
                <flux:button type="submit" variant="primary">Lücken-Maße speichern</flux:button>
            </div>
        </form>
    </flux:card>
</div>
