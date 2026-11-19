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
                            class="max-h-48 w-auto rounded-xl object-contain ring-1 ring-[#d0e3f9] dark:ring-white/10"
                        />
                    @else
                        <div class="flex h-48 w-36 items-center justify-center rounded-xl bg-[#d0e3f9]/40 ring-1 ring-[#d0e3f9] dark:bg-[#04214e] dark:ring-white/10">
                            <flux:icon name="document" class="size-16 text-[#456494]" />
                        </div>
                    @endif
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="pe-10">
                        <h2 class="text-lg font-semibold text-[#073070] dark:text-white">{{ $document->title }}</h2>
                        @if($document->trashed())
                            <flux:badge color="red" class="mt-2">Gelöscht</flux:badge>
                        @endif
                    </div>

                    <dl class="hwk-rows divide-y divide-[#d0e3f9] dark:divide-white/10">
                        <div>
                            <dt>Kategorie</dt>
                            <dd>{{ $document->category?->name }}</dd>
                        </div>
                        <div>
                            <dt>GVP</dt>
                            <dd>
                                {{ $document->effectiveGvp()?->name ?? '—' }}
                                @if($document->hasInvalidStoredGvp())
                                    <span class="text-amber-700 dark:text-amber-400">(Fallback)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Uploader</dt>
                            <dd>{{ $document->uploader?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Verantwortlich</dt>
                            <dd>{{ $document->responsible?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Gültig bis</dt>
                            <dd>{{ $document->gueltig_bis?->format('d.m.Y') ?? 'ohne Frist' }}</dd>
                        </div>
                        <div>
                            <dt>Aktiv</dt>
                            <dd>{{ $document->aktiv ? 'Ja' : 'Nein' }}</dd>
                        </div>
                        <div>
                            <dt>Kenntnisnahme</dt>
                            <dd>
                                @if(! $document->requires_acknowledgment)
                                    Nein
                                @elseif($document->usesFormwerkAcknowledgment())
                                    Unterschrift (Formwerk)
                                @else
                                    Passworteingabe
                                @endif
                            </dd>
                        </div>
                        @if($document->is_onboarding_it || $document->is_onboarding_perso)
                            <div>
                                <dt>Onboarding</dt>
                                <dd>
                                    @if($document->is_onboarding_it) IT @endif
                                    @if($document->is_onboarding_perso) Perso @endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @can('update', $document)
                        @if($document->hasInvalidStoredGvp())
                            @php
                                $storedGvpLabel = $document->gvp
                                    ? ($document->gvp->name.' (ID '.$document->gvp_id.')')
                                    : ('ID '.$document->gvp_id.' – Datensatz fehlt');
                                $fallbackGvp = $document->responsibleFallbackGvp();
                            @endphp
                            <div class="flex items-start gap-3 rounded-xl bg-white/80 p-4 ring-1 ring-[#d0e3f9] dark:bg-white/5 dark:ring-white/10">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#073070]/10 text-[#073070] dark:bg-white/10 dark:text-white">
                                    <flux:icon icon="exclamation-triangle" variant="micro" class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#073070] dark:text-white">Gespeicherte GVP nicht mehr gültig</p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                                        Die gespeicherte GVP „{{ $storedGvpLabel }}“ ist nicht mehr gültig.
                                        @if($fallbackGvp)
                                            Für die Anzeige wird die GVP des Verantwortlichen verwendet: {{ $fallbackGvp->name }}.
                                        @else
                                            Der Verantwortliche hat derzeit keine gültige GVP – bitte Verantwortlichen bzw. User-Sync prüfen.
                                        @endif
                                    </p>
                                    @if($fallbackGvp)
                                        <div class="mt-3">
                                            <flux:button
                                                size="sm"
                                                variant="primary"
                                                wire:click="adoptResponsibleGvp"
                                                wire:confirm="Gespeicherte GVP durch „{{ $fallbackGvp->name }}“ ersetzen?"
                                            >GVP des Verantwortlichen speichern</flux:button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endcan

                    @if($document->description)
                        <p class="text-sm text-slate-600 dark:text-white/70">{{ $document->description }}</p>
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
                    <flux:button
                        :href="route('apps.dokumente.inline', $document)"
                        icon="arrow-top-right-on-square"
                        target="_blank"
                        rel="noopener noreferrer"
                        tooltip="Öffnet immer die aktuelle Version im Browser"
                    >Im Browser öffnen</flux:button>
                    <flux:button
                        icon="link"
                        variant="filled"
                        tooltip="Link zur aktuellen Version kopieren"
                        x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from(route('apps.dokumente.inline', $document)) }}).then(() => $flux.toast({ text: 'Link kopiert', variant: 'success' })).catch(() => $flux.toast({ text: 'Kopieren fehlgeschlagen', variant: 'danger' }))"
                    >Link kopieren</flux:button>
                @endif

                @if($document->requires_acknowledgment && auth()->user()?->can('viewAcknowledgmentReport', $document))
                    <flux:button
                        icon="clipboard-document-check"
                        wire:click="openAcknowledgmentReport({{ $document->id }})"
                    >Auswertung</flux:button>
                @endif

                @if($this->detailNeedsAcknowledgment)
                    @if($document->usesFormwerkAcknowledgment())
                        <flux:button
                            :href="route('apps.dokumente.formwerk', $document)"
                            icon="pencil-square"
                            variant="primary"
                        >Per Unterschrift bestätigen</flux:button>
                    @elseif($detailDownloadClicked)
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
                        <p class="w-full text-sm text-slate-600 dark:text-white/70">
                            Bitte laden Sie das Dokument zuerst herunter, bevor Sie die Kenntnisnahme bestätigen.
                        </p>
                    @endif
                @endif

                @can('update', $document)
                    <flux:button wire:click="openEditModal({{ $document->id }})" variant="filled" icon="pencil-square">Bearbeiten</flux:button>
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

            <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Versionen</h3>
                </div>
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
            </section>

            <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Historie</h3>
                </div>
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
            </section>
        </div>
    @endif
</flux:modal>

<flux:modal wire:model="showExtendModal" class="max-w-lg">
    <form wire:submit="saveExtend" class="space-y-4">
        <div class="pe-10">
            <h2 class="text-lg font-semibold text-[#073070] dark:text-white">Gültigkeit verlängern</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-white/70">Neue Frist für die aktuelle Version festlegen.</p>
        </div>
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
        <div class="pe-10">
            <h2 class="text-lg font-semibold text-[#073070] dark:text-white">Dokument aktualisieren (neue Version)</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-white/70">Neue Datei und die Angaben der nächsten Version.</p>
        </div>
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
        @include('intranet-app-dokumente::partials.acknowledgment-method-fields', [
            'requiresModel' => 'updateRequiresAcknowledgment',
            'methodModel' => 'updateAcknowledgmentMethod',
            'urlModel' => 'updateFormwerkFormUrl',
            'customModel' => 'updateFormwerkUseCustomPayload',
            'rowsModel' => 'updateFormwerkPayload',
            'onboardingItModel' => 'updateIsOnboardingIt',
            'onboardingPersoModel' => 'updateIsOnboardingPerso',
            'target' => 'update',
            'showAcknowledgment' => false,
        ])
        @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::kenntnisnahme())
            @include('intranet-app-dokumente::partials.acknowledgment-method-fields', [
                'requiresModel' => 'updateRequiresAcknowledgment',
                'methodModel' => 'updateAcknowledgmentMethod',
                'urlModel' => 'updateFormwerkFormUrl',
                'urlModeModel' => 'updateFormwerkUrlMode',
                'customModel' => 'updateFormwerkUseCustomPayload',
                'rowsModel' => 'updateFormwerkPayload',
                'onboardingItModel' => 'updateIsOnboardingIt',
                'onboardingPersoModel' => 'updateIsOnboardingPerso',
                'reAcknowledgmentModel' => 'updateRequireReAcknowledgment',
                'target' => 'update',
                'showOnboarding' => false,
            ])
        @else
            @if($updateRequiresAcknowledgment)
                <flux:switch
                    wire:model="updateRequireReAcknowledgment"
                    label="Erneute Kenntnisnahme aller Benutzer erforderlich"
                    description="Wenn deaktiviert, gilt die neue Version ohne erneute Bestätigung als zur Kenntnis genommen."
                />
            @endif
        @endcan
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
                <p class="text-sm text-red-600">{{ $message }}</p>
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

<flux:modal wire:model="showEditModal" class="max-w-2xl">
    <form wire:submit="saveEdit" class="space-y-4">
        <div class="pe-10">
            <h2 class="text-lg font-semibold text-[#073070] dark:text-white">Dokument bearbeiten</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-white/70">Titel, Beschreibung und Einstellungen. Die Datei bleibt die aktuelle Version.</p>
        </div>
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
        @include('intranet-app-dokumente::partials.acknowledgment-method-fields', [
            'requiresModel' => 'updateRequiresAcknowledgment',
            'methodModel' => 'updateAcknowledgmentMethod',
            'urlModel' => 'updateFormwerkFormUrl',
            'customModel' => 'updateFormwerkUseCustomPayload',
            'rowsModel' => 'updateFormwerkPayload',
            'onboardingItModel' => 'updateIsOnboardingIt',
            'onboardingPersoModel' => 'updateIsOnboardingPerso',
            'target' => 'update',
            'showAcknowledgment' => false,
        ])
        @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::kenntnisnahme())
            @include('intranet-app-dokumente::partials.acknowledgment-method-fields', [
                'requiresModel' => 'updateRequiresAcknowledgment',
                'methodModel' => 'updateAcknowledgmentMethod',
                'urlModel' => 'updateFormwerkFormUrl',
                'urlModeModel' => 'updateFormwerkUrlMode',
                'customModel' => 'updateFormwerkUseCustomPayload',
                'rowsModel' => 'updateFormwerkPayload',
                'onboardingItModel' => 'updateIsOnboardingIt',
                'onboardingPersoModel' => 'updateIsOnboardingPerso',
                'target' => 'update',
                'showOnboarding' => false,
            ])
        @endcan
        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Speichern</flux:button>
            <flux:button type="button" variant="ghost" wire:click="$set('showEditModal', false)">Abbrechen</flux:button>
        </div>
    </form>
</flux:modal>

@if($acknowledgmentReportDocumentId)
    <livewire:intranet-app-dokumente::apps.dokumente.acknowledgment-report
        :document-id="$acknowledgmentReportDocumentId"
        :report-key="$acknowledgmentReportKey"
        :key="'ack-report-'.$acknowledgmentReportDocumentId.'-'.$acknowledgmentReportKey"
    />
@endif
