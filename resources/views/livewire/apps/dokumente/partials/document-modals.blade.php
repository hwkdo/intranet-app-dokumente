@php
    /** @var \Hwkdo\IntranetAppDokumente\Models\Document|null $document */
    $document = $this->detailDocument;
@endphp

<flux:modal wire:model="showDetailModal" class="max-w-4xl">
    @if($document)
        @php
            $detailMedia = $document->currentVersion?->getFirstMedia('document');
        @endphp
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="shrink-0">
                    @if($detailMedia && $detailMedia->hasGeneratedConversion('thumb'))
                        <img
                            src="{{ $detailMedia->getUrl('thumb') }}"
                            alt="Vorschau {{ $document->title }}"
                            class="max-h-48 w-auto rounded border border-zinc-200 object-contain dark:border-zinc-700"
                        />
                    @else
                        <div class="flex h-48 w-36 items-center justify-center rounded border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:icon name="document" class="size-16 text-zinc-400" />
                        </div>
                    @endif
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div>
                        <flux:heading size="lg">{{ $document->title }}</flux:heading>
                        @if($document->trashed())
                            <flux:badge color="red" class="mt-2">Gelöscht</flux:badge>
                        @endif
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <flux:text><strong>Kategorie:</strong> {{ $document->category?->name }}</flux:text>
                        <flux:text><strong>GVP:</strong> {{ $document->gvp?->name ?? '—' }}</flux:text>
                        <flux:text><strong>Uploader:</strong> {{ $document->uploader?->name ?? '—' }}</flux:text>
                        <flux:text><strong>Verantwortlich:</strong> {{ $document->responsible?->name ?? '—' }}</flux:text>
                        <flux:text><strong>Gültig bis:</strong> {{ $document->gueltig_bis?->format('d.m.Y') ?? 'ohne Frist' }}</flux:text>
                        <flux:text><strong>Aktiv:</strong> {{ $document->aktiv ? 'Ja' : 'Nein' }}</flux:text>
                        <flux:text><strong>Kenntnisnahme:</strong> {{ $document->requires_acknowledgment ? 'Ja' : 'Nein' }}</flux:text>
                        @if($document->is_onboarding_it || $document->is_onboarding_perso)
                            <flux:text>
                                <strong>Onboarding:</strong>
                                @if($document->is_onboarding_it) IT @endif
                                @if($document->is_onboarding_perso) Perso @endif
                            </flux:text>
                        @endif
                    </div>

                    @if($document->description)
                        <flux:text>{{ $document->description }}</flux:text>
                    @endif
                </div>
            </div>

                            <div class="flex flex-wrap gap-2">
                @if($document->currentVersion)
                    <flux:button
                        :href="route('apps.dokumente.download', $document)"
                        icon="arrow-down-tray"
                        variant="primary"
                        wire:click="markDetailDownloaded"
                    >Download</flux:button>
                @endif

                @if($document->requires_acknowledgment && auth()->user()?->can('viewAcknowledgmentReport', $document))
                    <flux:button
                        icon="clipboard-document-check"
                        wire:click="openAcknowledgmentReport({{ $document->id }})"
                    >Auswertung</flux:button>
                @endif

                @if($this->detailNeedsAcknowledgment)
                    @if($detailDownloadClicked)
                        <flux:button
                            :href="route('apps.dokumente.acknowledge', $document)"
                            icon="check-badge"
                            variant="primary"
                        >Kenntnisnahme bestätigen</flux:button>
                    @else
                        <flux:button
                            disabled
                            icon="check-badge"
                            variant="primary"
                            title="Bitte zuerst das Dokument herunterladen"
                        >Kenntnisnahme bestätigen</flux:button>
                        <flux:text class="w-full text-sm text-zinc-500">
                            Bitte laden Sie das Dokument zuerst herunter, bevor Sie die Kenntnisnahme bestätigen.
                        </flux:text>
                    @endif
                @endif

                @can('update', $document)
                    <flux:button wire:click="openUpdateModal({{ $document->id }})" variant="filled">Aktualisieren</flux:button>
                    <flux:button wire:click="openExtendModal({{ $document->id }})" variant="filled">Verlängern</flux:button>
                @endcan
                @can('delete', $document)
                    @if(! $document->trashed())
                        <flux:button
                            wire:click="deleteDocument({{ $document->id }})"
                            wire:confirm="Dokument wirklich löschen?"
                            variant="danger"
                        >Löschen</flux:button>
                    @endif
                @endcan
            </div>

            <div>
                <flux:heading size="md" class="mb-2">Versionen</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Version</flux:table.column>
                        <flux:table.column>Datum</flux:table.column>
                        <flux:table.column>Uploader</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($document->versions as $version)
                            <flux:table.row>
                                <flux:table.cell>
                                    v{{ $version->version_number }}
                                    @if($document->current_version_id === $version->id)
                                        <flux:badge size="sm" class="ml-1">aktuell</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $version->created_at?->format('d.m.Y H:i') }}</flux:table.cell>
                                <flux:table.cell>{{ $version->uploader?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        :href="route('apps.dokumente.download.version', [$document, $version])"
                                        wire:click="markDetailDownloaded({{ $version->id }})"
                                    >Download</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div>
                <flux:heading size="md" class="mb-2">Historie</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Ereignis</flux:table.column>
                        <flux:table.column>Zeitpunkt</flux:table.column>
                        <flux:table.column>Benutzer</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($document->histories as $history)
                            <flux:table.row>
                                <flux:table.cell>{{ $history->event?->label() ?? $history->event }}</flux:table.cell>
                                <flux:table.cell>{{ $history->created_at?->format('d.m.Y H:i') }}</flux:table.cell>
                                <flux:table.cell>{{ $history->user?->name ?? '—' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif
</flux:modal>

<flux:modal wire:model="showExtendModal" class="max-w-lg">
    <form wire:submit="saveExtend" class="space-y-4">
        <flux:heading size="lg">Gültigkeit verlängern</flux:heading>
        <flux:radio.group wire:model.live="extendMode" label="Art">
            <flux:radio value="auto" label="Automatisch um 1 Jahr" />
            <flux:radio value="manual" label="Manuelles Datum (max. 2 Jahre)" />
        </flux:radio.group>
        @if($extendMode === 'manual')
            <flux:input type="date" wire:model="extendManualDate" label="Neues Gültig-bis" />
        @endif
        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Speichern</flux:button>
            <flux:button type="button" variant="ghost" wire:click="$set('showExtendModal', false)">Abbrechen</flux:button>
        </div>
    </form>
</flux:modal>

<flux:modal wire:model="showUpdateModal" class="max-w-2xl">
    <form wire:submit="saveUpdateVersion" class="space-y-4">
        <flux:heading size="lg">Dokument aktualisieren (neue Version)</flux:heading>
        <flux:input wire:model="updateTitle" label="Titel" required />
        <flux:textarea wire:model="updateDescription" label="Beschreibung" rows="3" />
        <flux:input type="date" wire:model="updateGueltigBis" label="Gültig bis" />
        <flux:switch wire:model="updateAktiv" label="Aktiv" />
        <flux:select wire:model="updateCategoryId" label="Kategorie">
            @foreach($this->categoriesForSelect as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="updateResponsibleId" variant="listbox" searchable label="Verantwortliche/r" placeholder="Verantwortliche/n suchen…">
            @foreach($this->usersForSelect as $id => $label)
                <flux:select.option value="{{ $id }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::kenntnisnahme())
            <flux:switch wire:model.live="updateRequiresAcknowledgment" label="Zur Kenntnisnahme" />
        @endcan
        @if($updateRequiresAcknowledgment)
            <flux:switch
                wire:model="updateRequireReAcknowledgment"
                label="Erneute Kenntnisnahme aller Benutzer erforderlich"
                description="Wenn deaktiviert, gilt die neue Version ohne erneute Bestätigung als zur Kenntnis genommen."
            />
        @endif
        <flux:switch wire:model="updateShowInNewsSlider" label="In News-Slider anzeigen" />
        <flux:radio.group wire:model.live="updateNewsTitleImageMode" label="News-Titelbild">
            <flux:radio value="auto" label="Automatisch generiertes Titelbild verwenden" description="Dokument-Vorschau im Rahmenbild" />
            <flux:radio value="custom" label="Eigenes Titelbild" description="Eigenes Bild hochladen" />
            <flux:radio value="default" label="Standard-News-Titelbild verwenden" description="Allgemeines Intranet-Standardbild" />
        </flux:radio.group>
        @if($updateNewsTitleImageMode === 'custom')
            <flux:file-upload wire:model="updateNewsTitleImage" label="Eigenes Titelbild">
                <flux:file-upload.dropzone
                    heading="Titelbild hierher ziehen oder auswählen"
                    text="JPG, PNG, WebP · max. 10 MB"
                    with-progress
                />
            </flux:file-upload>
            @if($updateNewsTitleImage)
                <flux:file-item :heading="$updateNewsTitleImage->getClientOriginalName()" :size="$updateNewsTitleImage->getSize()">
                    <x-slot:actions>
                        <flux:file-item.remove wire:click="$set('updateNewsTitleImage', null)" />
                    </x-slot:actions>
                </flux:file-item>
            @endif
            @error('updateNewsTitleImage')
                <flux:text class="text-red-600">{{ $message }}</flux:text>
            @enderror
        @endif
        <flux:file-upload wire:model="updateFile" label="Neue Datei">
            <flux:file-upload.dropzone
                heading="Datei hierher ziehen oder auswählen"
                text="Max. 50 MB"
                with-progress
            />
        </flux:file-upload>
        @if($updateFile)
            <flux:file-item :heading="$updateFile->getClientOriginalName()" :size="$updateFile->getSize()">
                <x-slot:actions>
                    <flux:file-item.remove wire:click="$set('updateFile', null)" />
                </x-slot:actions>
            </flux:file-item>
        @endif
        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Version speichern</flux:button>
            <flux:button type="button" variant="ghost" wire:click="$set('showUpdateModal', false)">Abbrechen</flux:button>
        </div>
    </form>
</flux:modal>

@if($acknowledgmentReportDocumentId)
    <livewire:intranet-app-dokumente::apps.dokumente.acknowledgment-report
        :document-id="$acknowledgmentReportDocumentId"
        :key="'ack-report-'.$acknowledgmentReportDocumentId"
    />
@endif
