
<div>
<x-intranet-app-dokumente::dokumente-layout heading="Dokumente" subheading="Übersicht nach Organisationseinheit und Kategorie.">
<div class="space-y-4">
    @php
        $structure = $this->gvpStructure;
        $hgf = $structure['hgf'];
        $stabs = $structure['stabs'];
        $gbs = $structure['gbs'];
        $categories = $this->categories;
    @endphp

    @if($hgf)
        <div class="glass-card sticky top-3 z-20 flex flex-wrap items-center gap-2 p-3" style="--glass-opacity: 0.92">
            @if($this->allDocumentsCount > 0)
                <flux:button
                    wire:click="openDocumentListModal(null, '{{ implode(',', $hgf->getDescendantIds()) }}', 'Alle', '{{ addslashes($hgf->bezeichnung) }}')"
                    variant="primary"
                >
                    Alle {{ $this->allDocumentsCount }} Dokumente
                </flux:button>
            @endif
            @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::upload())
                <flux:button variant="primary" icon="arrow-up-tray" wire:click="openUploadModal">
                    Dokument hochladen
                </flux:button>
            @endcan
        </div>

        <x-intranet-app-dokumente::hwk-card
            title="Dokumenten-Matrix"
            icon="table-cells"
            description="Anzahl je Organisationseinheit und Kategorie. Ein Klick öffnet die Liste."
        >
        <div
            class="overflow-x-auto"
            x-data="{
                stabOpen: @js($stabOpened),
                openedGbIds: @js($openedGbIds),
                toggleStab() {
                    this.stabOpen = !this.stabOpen;
                    $wire.toggleStab();
                },
                toggleGb(id) {
                    const i = this.openedGbIds.indexOf(id);
                    if (i >= 0) this.openedGbIds.splice(i, 1);
                    else this.openedGbIds.push(id);
                    $wire.toggleGb(id);
                }
            }"
        >
            <style>
                [x-cloak] { display: none !important; }
                /*
                 * Flux-Tabellen sind table-fixed + min-w-full. Ohne explizite Breite
                 * für die GVP-Spalte landet der Restplatz dort (Ultrawide-Leerspalte).
                 */
                [data-dokumente-matrix] th[data-flux-column]:first-child,
                [data-dokumente-matrix] td:first-child {
                    width: 18rem;
                    max-width: 18rem;
                    white-space: normal;
                    overflow-wrap: anywhere;
                }
                /*
                 * Summe muss den Zähl-Chip samt Zellen-Padding fassen.
                 * 3rem ließ dreistellige Werte über den Tabellenrand laufen
                 * und hielt den horizontalen Scrollbalken auch bei voller Breite.
                 */
                [data-dokumente-matrix] th[data-flux-column]:last-child,
                [data-dokumente-matrix] td:last-child {
                    width: 4.5rem;
                }
                [data-dokumente-matrix] th[data-flux-column]:not(:first-child):not(:last-child),
                [data-dokumente-matrix] td:not(:first-child):not(:last-child) {
                    width: auto;
                    max-width: none;
                    min-width: 6rem;
                    white-space: normal;
                }
            </style>
            <flux:table class="w-full" data-dokumente-matrix>
                <flux:table.columns>
                    <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! w-72 max-w-72 whitespace-normal"></flux:table.column>
                    @foreach($categories as $cat)
                        <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! text-center" align="center">
                            <span class="block w-full text-center whitespace-normal break-words hyphens-auto text-sm">{{ $cat->name }}</span>
                        </flux:table.column>
                    @endforeach
                    <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! text-center">∑</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    {{-- HGF row (nur Dokumente mit gvp_id = HGF, kein Rollup) --}}
                    <flux:table.row>
                        @php $hgfDirectId = (string) $hgf->id; $m = $this->countMatrix['hgf']; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium whitespace-normal">
                            {{ $hgf->bezeichnung }}
                        </flux:table.cell>
                        @foreach($categories as $cat)
                            @php $cnt = $m[$cat->id] ?? 0; @endphp
                            <flux:table.cell class="bg-white/50 dark:bg-[#04214e]/40 text-center">
                                <x-intranet-app-dokumente::matrix-count
                                    :count="$cnt"
                                    wire:click="openDocumentListModal({{ $cat->id }}, '{{ $hgfDirectId }}', '{{ addslashes($cat->name) }}', '{{ addslashes($hgf->bezeichnung) }}')"
                                />
                            </flux:table.cell>
                        @endforeach
                        @php $hgfAll = $m['all'] ?? 0; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                            <x-intranet-app-dokumente::matrix-count
                                :count="$hgfAll"
                                variant="navy"
                                wire:click="openDocumentListModal(null, '{{ $hgfDirectId }}', 'Alle', '{{ addslashes($hgf->bezeichnung) }}')"
                            />
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Stab row (click to expand) --}}
                    <flux:table.row>
                        @php $stabIds = $this->getStabGvpIds(); $stabIdsStr = implode(',', $stabIds); $stabM = $this->countMatrix['stab']; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium cursor-pointer select-none whitespace-normal" @click="toggleStab()">
                            Stab
                        </flux:table.cell>
                        @foreach($categories as $cat)
                            @php $cnt = $stabM[$cat->id] ?? 0; @endphp
                            <flux:table.cell class="bg-white/50 dark:bg-[#04214e]/40 text-center">
                                <x-intranet-app-dokumente::matrix-count
                                    :count="$cnt"
                                    wire:click="openDocumentListModal({{ $cat->id }}, '{{ $stabIdsStr }}', '{{ addslashes($cat->name) }}', 'Stab')"
                                />
                            </flux:table.cell>
                        @endforeach
                        @php $stabAll = $stabM['all'] ?? 0; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                            <x-intranet-app-dokumente::matrix-count
                                :count="$stabAll"
                                variant="navy"
                                wire:click="openDocumentListModal(null, '{{ $stabIdsStr }}', 'Alle', 'Stab')"
                            />
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Stab sub-rows (Optimistic UI: immer im DOM, Sichtbarkeit per Alpine) --}}
                    @foreach($stabs as $stab)
                        @php $stabDirect = $this->countMatrix['stabDirect'][$stab->id] ?? []; $stabDocCount = $stabDirect['all'] ?? 0; @endphp
                        <flux:table.row x-show="stabOpen" x-cloak>
                                <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white! whitespace-normal">{{ $stab->bezeichnung }}</flux:table.cell>
                                @foreach($categories as $cat)
                                    @php $cnt = $stabDirect[$cat->id] ?? 0; @endphp
                                    <flux:table.cell class="text-center">
                                        <x-intranet-app-dokumente::matrix-count
                                            :count="$cnt"
                                            wire:click="openDocumentListModal({{ $cat->id }}, '{{ $stab->id }}', '{{ addslashes($cat->name) }}', '{{ addslashes($stab->bezeichnung) }}')"
                                        />
                                    </flux:table.cell>
                                @endforeach
                                <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                    <x-intranet-app-dokumente::matrix-count
                                        :count="$stabDocCount"
                                        variant="navy"
                                        wire:click="openDocumentListModal(null, '{{ $stab->id }}', 'Alle', '{{ addslashes($stab->bezeichnung) }}')"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                    @endforeach

                    {{-- GB rows (each GB, then if expanded: Geschäftsführung + Abteilungen) --}}
                    @foreach($gbs as $gb)
                        @php $gbIds = implode(',', $gb->getDescendantIds()); $gbM = $this->countMatrix['gb'][$gb->id] ?? []; @endphp
                        <flux:table.row>
                            <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium cursor-pointer select-none whitespace-normal" @click="toggleGb({{ $gb->id }})">
                                {{ $gb->bezeichnung }}
                            </flux:table.cell>
                            @foreach($categories as $cat)
                                @php $cnt = $gbM[$cat->id] ?? 0; @endphp
                                <flux:table.cell class="bg-white/50 dark:bg-[#04214e]/40 text-center">
                                    <x-intranet-app-dokumente::matrix-count
                                        :count="$cnt"
                                        wire:click="openDocumentListModal({{ $cat->id }}, '{{ $gbIds }}', '{{ addslashes($cat->name) }}', '{{ addslashes($gb->bezeichnung) }}')"
                                    />
                                </flux:table.cell>
                            @endforeach
                            @php $gbAll = $gbM['all'] ?? 0; @endphp
                            <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                <x-intranet-app-dokumente::matrix-count
                                    :count="$gbAll"
                                    variant="navy"
                                    wire:click="openDocumentListModal(null, '{{ $gbIds }}', 'Alle', '{{ addslashes($gb->bezeichnung) }}')"
                                />
                            </flux:table.cell>
                        </flux:table.row>

                        {{-- GB aufgeklappt (Optimistic UI: immer im DOM, Sichtbarkeit per Alpine) --}}
                        @php $gbDirectM = $this->countMatrix['gbDirect'][$gb->id] ?? []; $gbDirectCount = $gbDirectM['all'] ?? 0; @endphp
                        <flux:table.row x-show="openedGbIds.includes({{ $gb->id }})" x-cloak>
                                <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white! whitespace-normal">Geschäftsführung</flux:table.cell>
                                @foreach($categories as $cat)
                                    @php $cnt = $gbDirectM[$cat->id] ?? 0; @endphp
                                    <flux:table.cell class="text-center">
                                        <x-intranet-app-dokumente::matrix-count
                                            :count="$cnt"
                                            wire:click="openDocumentListModal({{ $cat->id }}, '{{ $gb->id }}', '{{ addslashes($cat->name) }}', 'Geschäftsführung')"
                                        />
                                    </flux:table.cell>
                                @endforeach
                                <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                    <x-intranet-app-dokumente::matrix-count
                                        :count="$gbDirectCount"
                                        variant="navy"
                                        wire:click="openDocumentListModal(null, '{{ $gb->id }}', 'Alle', 'Geschäftsführung')"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                            @foreach($gb->childGvps as $abt)
                                @php $abtIds = implode(',', $abt->getDescendantIds()); $abtM = $this->countMatrix['abt'][$abt->id] ?? []; @endphp
                                <flux:table.row x-show="openedGbIds.includes({{ $gb->id }})" x-cloak>
                                    <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white! whitespace-normal">{{ $abt->bezeichnung }}</flux:table.cell>
                                    @foreach($categories as $cat)
                                        @php $cnt = $abtM[$cat->id] ?? 0; @endphp
                                        <flux:table.cell class="text-center">
                                            <x-intranet-app-dokumente::matrix-count
                                                :count="$cnt"
                                                wire:click="openDocumentListModal({{ $cat->id }}, '{{ $abtIds }}', '{{ addslashes($cat->name) }}', '{{ addslashes($abt->bezeichnung) }}')"
                                            />
                                        </flux:table.cell>
                                    @endforeach
                                    @php $abtAll = $abtM['all'] ?? 0; @endphp
                                    <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                        <x-intranet-app-dokumente::matrix-count
                                            :count="$abtAll"
                                            variant="navy"
                                            wire:click="openDocumentListModal(null, '{{ $abtIds }}', 'Alle', '{{ addslashes($abt->bezeichnung) }}')"
                                        />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                    @endforeach

                    {{-- Sum row --}}
                    <flux:table.row>
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium whitespace-normal">∑</flux:table.cell>
                        @foreach($categories as $cat)
                            @php $cnt = $this->countMatrix['category'][$cat->id] ?? 0; @endphp
                            <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                <x-intranet-app-dokumente::matrix-count
                                    :count="$cnt"
                                    variant="navy"
                                    wire:click="openDocumentListModalByCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                                />
                            </flux:table.cell>
                        @endforeach
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                            <x-intranet-app-dokumente::matrix-count
                                :count="$this->allDocumentsCount"
                                variant="navy"
                                wire:click="openDocumentListModalAll()"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        </div>
        </x-intranet-app-dokumente::hwk-card>

        {{-- Document list modal --}}
        <flux:modal wire:model="showDocumentListModal" name="document-list" class="md:max-w-4xl">
            <div class="space-y-4">
                <div class="pe-10">
                    <h2 class="text-lg font-semibold text-[#073070] dark:text-white">{{ $modalCategoryLabel }} in {{ $modalGvpLabel }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-white/70">Dokumente in dieser Auswahl. Die Suche filtert nach dem Titel.</p>
                </div>
                <flux:input
                    wire:model.live.debounce.300ms="modalSearch"
                    placeholder="Titel suchen…"
                    icon="magnifying-glass"
                    class="max-w-md"
                    aria-label="Titel suchen"
                />
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Thumbnail</flux:table.column>
                        <flux:table.column>Titel</flux:table.column>
                        <flux:table.column>Aktionen</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->filteredModalDocuments as $doc)
                            @php
                                $media = $doc->currentVersion?->getFirstMedia('document');
                            @endphp
                            <flux:table.row wire:key="modal-doc-{{ $doc->id }}">
                                <flux:table.cell>
                                    @if($media && $media->hasGeneratedConversion('thumb'))
                                        <img src="{{ $media->getUrl('thumb') }}" alt="" class="h-12 w-auto object-contain" />
                                    @else
                                        <flux:icon name="document" class="size-12 text-[#456494]" />
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <button type="button" class="text-left font-medium text-[#073070] dark:text-white" wire:click="openDetail({{ $doc->id }})">
                                        {{ $doc->title }}
                                    </button>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="sm" wire:click="openDetail({{ $doc->id }})" variant="primary">Details</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                @if($this->filteredModalDocuments->isEmpty())
                    <x-intranet-app-dokumente::hwk-empty
                        bare
                        icon="document-magnifying-glass"
                        :title="trim($modalSearch) !== '' ? 'Keine Treffer' : 'Keine Dokumente'"
                    >
                        @if(trim($modalSearch) !== '')
                            Keine Treffer für „{{ $modalSearch }}“.
                        @else
                            Keine Dokumente in dieser Auswahl.
                        @endif
                    </x-intranet-app-dokumente::hwk-empty>
                @endif
            </div>
        </flux:modal>

        @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::upload())
            <flux:modal wire:model="showUploadModal" name="upload-document" class="w-full md:max-w-5xl">
                <form wire:submit="saveUpload" class="space-y-4">
                    <div class="pe-10">
                        <h2 class="text-lg font-semibold text-[#073070] dark:text-white">Dokument hochladen</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-white/70">Datei auswählen, zuordnen und festlegen, ob Benutzer das Dokument bestätigen müssen.</p>
                    </div>

                    <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                        <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                            <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Dokument</h3>
                            <p class="text-xs text-slate-600 dark:text-white/70">Datei, Titel und Beschreibung.</p>
                        </div>
                        <div class="space-y-4 p-4">
                            <flux:file-upload wire:model="uploadFile" label="Datei">
                                <flux:file-upload.dropzone
                                    heading="Datei hierher ziehen oder auswählen"
                                    text="Max. 50 MB"
                                    with-progress
                                    inline
                                />
                            </flux:file-upload>
                            @if($uploadFile)
                                <flux:file-item :heading="$uploadFile->getClientOriginalName()" :size="$uploadFile->getSize()">
                                    <x-slot:actions>
                                        <flux:file-item.remove wire:click="$set('uploadFile', null)" />
                                    </x-slot:actions>
                                </flux:file-item>
                            @endif
                            @error('uploadFile')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <flux:input wire:model="uploadTitle" label="Titel" required />
                            <flux:textarea wire:model="uploadDescription" label="Beschreibung" rows="3" />
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                        <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                            <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Zuordnung</h3>
                            <p class="text-xs text-slate-600 dark:text-white/70">Kategorie, Verantwortung und Gültigkeit.</p>
                        </div>
                        <div class="grid items-start gap-4 p-4 sm:grid-cols-2">
                            <flux:select wire:model.live="uploadCategoryId" variant="listbox" label="Kategorie" placeholder="Kategorie auswählen" required>
                                @foreach($this->categories as $cat)
                                    <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model.live="uploadResponsibleId" variant="listbox" searchable label="Verantwortliche/r" placeholder="Verantwortliche/n suchen…" required>
                                @foreach($this->usersForSelect as $id => $label)
                                    <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::chooseGvp())
                                <flux:select wire:model.live="uploadGvpId" variant="listbox" searchable label="GVP" placeholder="GVP auswählen" required>
                                    @foreach($this->gvpsForSelect as $id => $label)
                                        <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endcan
                            <flux:input wire:model="uploadGueltigBis" type="date" label="Gültig bis" description:trailing="Leer = unbegrenzt gültig" />
                            <div class="sm:col-span-2">
                                <flux:switch wire:model="uploadAktiv" align="left" label="Aktiv" description="Inaktive Dokumente erscheinen nicht in der Übersicht." />
                            </div>
                        </div>
                    </section>

                    @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::kenntnisnahme())
                        <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                            <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                                <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Kenntnisnahme</h3>
                                <p class="text-xs text-slate-600 dark:text-white/70">Ob und wie Benutzer das Dokument bestätigen müssen.</p>
                            </div>
                            <div class="p-4">
                                @include('intranet-app-dokumente::partials.acknowledgment-method-fields', ['showOnboarding' => false])
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                            <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                                <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Onboarding</h3>
                                <p class="text-xs text-slate-600 dark:text-white/70">Dokument neuen Mitarbeitenden im Onboarding bereitstellen.</p>
                            </div>
                            <div class="p-4">
                                @include('intranet-app-dokumente::partials.acknowledgment-method-fields', ['showAcknowledgment' => false])
                            </div>
                        </section>
                    @endcan

                    <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                        <div class="border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                            <h3 class="text-sm font-semibold text-[#073070] dark:text-white">News</h3>
                            <p class="text-xs text-slate-600 dark:text-white/70">Beim Hochladen wird immer eine News in der Kategorie „{{ \Hwkdo\IntranetAppDokumente\Services\DocumentNewsService::CATEGORY_NAME }}“ veröffentlicht.</p>
                        </div>
                        <div class="space-y-4 p-4">
                            <flux:radio.group wire:model.live="uploadNewsTitleImageMode" label="Titelbild der News" variant="cards" class="max-lg:flex-col">
                                <flux:radio value="auto" icon="sparkles" label="Automatisch" description="Dokument-Vorschau im Rahmenbild" />
                                <flux:radio value="custom" icon="photo" label="Eigenes Bild" description="Eigenes Bild hochladen" />
                                <flux:radio value="default" icon="newspaper" label="Standardbild" description="Allgemeines Intranet-Standardbild" />
                            </flux:radio.group>
                            @if($uploadNewsTitleImageMode === 'custom')
                                <flux:file-upload wire:model="uploadNewsTitleImage" label="Eigenes Titelbild">
                                    <flux:file-upload.dropzone
                                        heading="Titelbild hierher ziehen oder auswählen"
                                        text="JPG, PNG, WebP · max. 10 MB"
                                        with-progress
                                        inline
                                    />
                                </flux:file-upload>
                                @if($uploadNewsTitleImage)
                                    <flux:file-item :heading="$uploadNewsTitleImage->getClientOriginalName()" :size="$uploadNewsTitleImage->getSize()">
                                        <x-slot:actions>
                                            <flux:file-item.remove wire:click="$set('uploadNewsTitleImage', null)" />
                                        </x-slot:actions>
                                    </flux:file-item>
                                @endif
                                @error('uploadNewsTitleImage')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                            <flux:switch
                                wire:model="uploadShowInNewsSlider"
                                align="left"
                                label="Zusätzlich im News-Slider anzeigen"
                                description="Die News erscheint auch ohne Slider in der News-Übersicht."
                            />
                        </div>
                    </section>

                    @if($showUploadModal)
                        @php($recipients = $this->uploadRecipientEstimate)
                        <section class="overflow-hidden rounded-xl ring-1 ring-[#d0e3f9] dark:ring-white/10">
                            <div class="flex items-center gap-3 border-b border-[#d0e3f9] px-4 py-3 dark:border-white/10">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#073070]/10 text-[#073070] dark:bg-white/10 dark:text-white">
                                    <flux:icon icon="bell" variant="micro" class="size-5" />
                                </span>
                                <h3 class="text-sm font-semibold text-[#073070] dark:text-white">Benachrichtigungen beim Speichern</h3>
                            </div>
                            <div class="px-4 py-3 text-sm text-slate-600 dark:text-white/70">
                                <ul class="space-y-1">
                                    <li wire:key="upload-recipients-news">
                                        <strong>{{ number_format($recipients['news'], 0, ',', '.') }}</strong>
                                        {{ $recipients['news'] === 1 ? 'Benutzer erhält' : 'Benutzer erhalten' }}
                                        eine News-Benachrichtigung (News-Kategorie „{{ $recipients['newsCategory'] }}“, laut Benachrichtigungseinstellungen).
                                    </li>
                                    @if($uploadRequiresAcknowledgment)
                                        <li wire:key="upload-recipients-acknowledgment" x-show="$wire.uploadRequiresAcknowledgment">
                                            <strong>{{ number_format($recipients['acknowledgment'], 0, ',', '.') }}</strong>
                                            {{ $recipients['acknowledgment'] === 1 ? 'Benutzer wird' : 'Benutzer werden' }}
                                            zur Kenntnisnahme aufgefordert (Pflicht-Benachrichtigung, nicht abbestellbar).
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </section>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:spacer />
                        <flux:button type="button" wire:click="closeUploadModal" variant="ghost">Abbrechen</flux:button>
                        <flux:button type="submit" variant="primary" icon="arrow-up-tray">Hochladen</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcan

        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    @else
        <x-intranet-app-dokumente::hwk-empty icon="table-cells" title="Keine GVP-Struktur vorhanden">
            Es ist keine Organisationsstruktur hinterlegt, aus der die Matrix aufgebaut werden kann.
        </x-intranet-app-dokumente::hwk-empty>
    @endif
</div>
</x-intranet-app-dokumente::dokumente-layout>
</div>
