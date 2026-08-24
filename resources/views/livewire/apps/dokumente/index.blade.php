<x-intranet-app-dokumente::dokumente-layout heading="Dokumente App" subheading="Übersicht">
<div>
    @php
        $structure = $this->gvpStructure;
        $hgf = $structure['hgf'];
        $stabs = $structure['stabs'];
        $gbs = $structure['gbs'];
        $categories = $this->categories;
    @endphp

    @if($hgf)
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @if($this->allDocumentsCount > 0)
                <flux:button
                    wire:click="openDocumentListModal(null, '{{ implode(',', $hgf->getDescendantIds()) }}', 'Alle', '{{ addslashes($hgf->bezeichnung) }}')"
                    variant="primary"
                >
                    Alle {{ $this->allDocumentsCount }} Dokumente
                </flux:button>
            @endif
            @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::upload())
                <flux:button variant="primary" wire:click="openUploadModal">
                    Dokument hochladen
                </flux:button>
            @endcan
        </div>

        <flux:card class="glass-card p-0!">
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
                [data-dokumente-matrix] th[data-flux-column]:last-child,
                [data-dokumente-matrix] td:last-child {
                    width: 3rem;
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
                    <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! text-center w-12">∑</flux:table.column>
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
                                @if($cnt > 0)
                                    <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $hgfDirectId }}', '{{ addslashes($cat->name) }}', '{{ addslashes($hgf->bezeichnung) }}')" class="text-blue-600 hover:underline">
                                        {{ $cnt }}
                                    </button>
                                @else
                                    {{ $cnt }}
                                @endif
                            </flux:table.cell>
                        @endforeach
                        @php $hgfAll = $m['all'] ?? 0; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                            @if($hgfAll > 0)
                                <button type="button" wire:click="openDocumentListModal(null, '{{ $hgfDirectId }}', 'Alle', '{{ addslashes($hgf->bezeichnung) }}')" class="text-white! hover:underline">
                                    {{ $hgfAll }}
                                </button>
                            @else
                                {{ $hgfAll }}
                            @endif
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
                                @if($cnt > 0)
                                    <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $stabIdsStr }}', '{{ addslashes($cat->name) }}', 'Stab')" class="text-blue-600 hover:underline">
                                        {{ $cnt }}
                                    </button>
                                @else
                                    {{ $cnt }}
                                @endif
                            </flux:table.cell>
                        @endforeach
                        @php $stabAll = $stabM['all'] ?? 0; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                            @if($stabAll > 0)
                                <button type="button" wire:click="openDocumentListModal(null, '{{ $stabIdsStr }}', 'Alle', 'Stab')" class="text-white! hover:underline">
                                    {{ $stabAll }}
                                </button>
                            @else
                                {{ $stabAll }}
                            @endif
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
                                        @if($cnt > 0)
                                            <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $stab->id }}', '{{ addslashes($cat->name) }}', '{{ addslashes($stab->bezeichnung) }}')" class="text-blue-600 hover:underline">
                                                {{ $cnt }}
                                            </button>
                                        @else
                                            {{ $cnt }}
                                        @endif
                                    </flux:table.cell>
                                @endforeach
                                <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                    @if($stabDocCount > 0)
                                        <button type="button" wire:click="openDocumentListModal(null, '{{ $stab->id }}', 'Alle', '{{ addslashes($stab->bezeichnung) }}')" class="text-white! hover:underline">
                                            {{ $stabDocCount }}
                                        </button>
                                    @else
                                        {{ $stabDocCount }}
                                    @endif
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
                                    @if($cnt > 0)
                                        <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $gbIds }}', '{{ addslashes($cat->name) }}', '{{ addslashes($gb->bezeichnung) }}')" class="text-blue-600 hover:underline">
                                            {{ $cnt }}
                                        </button>
                                    @else
                                        {{ $cnt }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                            @php $gbAll = $gbM['all'] ?? 0; @endphp
                            <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                @if($gbAll > 0)
                                    <button type="button" wire:click="openDocumentListModal(null, '{{ $gbIds }}', 'Alle', '{{ addslashes($gb->bezeichnung) }}')" class="text-white! hover:underline">
                                        {{ $gbAll }}
                                    </button>
                                @else
                                    {{ $gbAll }}
                                @endif
                            </flux:table.cell>
                        </flux:table.row>

                        {{-- GB aufgeklappt (Optimistic UI: immer im DOM, Sichtbarkeit per Alpine) --}}
                        @php $gbDirectM = $this->countMatrix['gbDirect'][$gb->id] ?? []; $gbDirectCount = $gbDirectM['all'] ?? 0; @endphp
                        <flux:table.row x-show="openedGbIds.includes({{ $gb->id }})" x-cloak>
                                <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white! whitespace-normal">Geschäftsführung</flux:table.cell>
                                @foreach($categories as $cat)
                                    @php $cnt = $gbDirectM[$cat->id] ?? 0; @endphp
                                    <flux:table.cell class="text-center">
                                        @if($cnt > 0)
                                            <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $gb->id }}', '{{ addslashes($cat->name) }}', 'Geschäftsführung')" class="text-blue-600 hover:underline">
                                                {{ $cnt }}
                                            </button>
                                        @else
                                            {{ $cnt }}
                                        @endif
                                    </flux:table.cell>
                                @endforeach
                                <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                    @if($gbDirectCount > 0)
                                        <button type="button" wire:click="openDocumentListModal(null, '{{ $gb->id }}', 'Alle', 'Geschäftsführung')" class="text-white! hover:underline">
                                            {{ $gbDirectCount }}
                                        </button>
                                    @else
                                        {{ $gbDirectCount }}
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                            @foreach($gb->childGvps as $abt)
                                @php $abtIds = implode(',', $abt->getDescendantIds()); $abtM = $this->countMatrix['abt'][$abt->id] ?? []; @endphp
                                <flux:table.row x-show="openedGbIds.includes({{ $gb->id }})" x-cloak>
                                    <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white! whitespace-normal">{{ $abt->bezeichnung }}</flux:table.cell>
                                    @foreach($categories as $cat)
                                        @php $cnt = $abtM[$cat->id] ?? 0; @endphp
                                        <flux:table.cell class="text-center">
                                            @if($cnt > 0)
                                                <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $abtIds }}', '{{ addslashes($cat->name) }}', '{{ addslashes($abt->bezeichnung) }}')" class="text-blue-600 hover:underline">
                                                    {{ $cnt }}
                                                </button>
                                            @else
                                                {{ $cnt }}
                                            @endif
                                        </flux:table.cell>
                                    @endforeach
                                    @php $abtAll = $abtM['all'] ?? 0; @endphp
                                    <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                                        @if($abtAll > 0)
                                            <button type="button" wire:click="openDocumentListModal(null, '{{ $abtIds }}', 'Alle', '{{ addslashes($abt->bezeichnung) }}')" class="text-white! hover:underline">
                                                {{ $abtAll }}
                                            </button>
                                        @else
                                            {{ $abtAll }}
                                        @endif
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
                                @if($cnt > 0)
                                    <button type="button" wire:click="openDocumentListModalByCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="text-white! hover:underline">
                                        {{ $cnt }}
                                    </button>
                                @else
                                    {{ $cnt }}
                                @endif
                            </flux:table.cell>
                        @endforeach
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! text-center">
                            @if($this->allDocumentsCount > 0)
                                <button type="button" wire:click="openDocumentListModalAll()" class="text-white! hover:underline">
                                    {{ $this->allDocumentsCount }}
                                </button>
                            @else
                                {{ $this->allDocumentsCount }}
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        </div>
        </flux:card>

        {{-- Document list modal --}}
        <flux:modal wire:model="showDocumentListModal" name="document-list" class="md:max-w-4xl">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $modalCategoryLabel }} in {{ $modalGvpLabel }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Thumbnail</flux:table.column>
                        <flux:table.column>Titel</flux:table.column>
                        <flux:table.column>Aktionen</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($modalDocuments ?? [] as $doc)
                            @php
                                $media = $doc->currentVersion?->getFirstMedia('document');
                            @endphp
                            <flux:table.row>
                                <flux:table.cell>
                                    @if($media && $media->hasGeneratedConversion('thumb'))
                                        <img src="{{ $media->getUrl('thumb') }}" alt="" class="h-12 w-auto object-contain" />
                                    @else
                                        <flux:icon name="document" class="size-12 text-zinc-400" />
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <button type="button" class="text-left underline" wire:click="openDetail({{ $doc->id }})">
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
                @if(($modalDocuments ?? collect())->isEmpty())
                    <flux:text>Keine Dokumente in dieser Auswahl.</flux:text>
                @endif
            </div>
        </flux:modal>

        @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::upload())
            <flux:modal wire:model="showUploadModal" name="upload-document" class="md:max-w-2xl">
                <form wire:submit="saveUpload" class="space-y-4">
                    <flux:heading size="lg" class="mb-4">Dokument hochladen</flux:heading>
                    <flux:input wire:model="uploadTitle" label="Titel" required />
                    <flux:textarea wire:model="uploadDescription" label="Beschreibung" rows="3" />
                    <flux:input wire:model="uploadGueltigBis" type="date" label="Gültig bis (leer = unbegrenzt)" />
                    <flux:switch wire:model="uploadAktiv" label="Aktiv" />
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
                    <flux:select wire:model.live="uploadCategoryId" variant="listbox" label="Kategorie" placeholder="Kategorie auswählen" required>
                        @foreach($this->categories as $cat)
                            <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @can(\Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::kenntnisnahme())
                        <flux:switch wire:model="uploadRequiresAcknowledgment" label="Zur Kenntnisnahme" />
                    @endcan
                    <flux:switch wire:model="uploadShowInNewsSlider" label="In News-Slider anzeigen" />
                    <flux:radio.group wire:model.live="uploadNewsTitleImageMode" label="News-Titelbild">
                        <flux:radio value="auto" label="Automatisch generiertes Titelbild verwenden" description="Dokument-Vorschau im Rahmenbild" />
                        <flux:radio value="custom" label="Eigenes Titelbild" description="Eigenes Bild hochladen" />
                        <flux:radio value="default" label="Standard-News-Titelbild verwenden" description="Allgemeines Intranet-Standardbild" />
                    </flux:radio.group>
                    @if($uploadNewsTitleImageMode === 'custom')
                        <flux:file-upload wire:model="uploadNewsTitleImage" label="Eigenes Titelbild">
                            <flux:file-upload.dropzone
                                heading="Titelbild hierher ziehen oder auswählen"
                                text="JPG, PNG, WebP · max. 10 MB"
                                with-progress
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
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    @endif
                    <flux:file-upload wire:model="uploadFile" label="Datei">
                        <flux:file-upload.dropzone
                            heading="Datei hierher ziehen oder auswählen"
                            text="Max. 50 MB"
                            with-progress
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
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                    <div class="flex gap-2 pt-4">
                        <flux:button type="submit" variant="primary">Speichern</flux:button>
                        <flux:button type="button" wire:click="closeUploadModal" variant="ghost">Abbrechen</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcan

        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    @else
        <flux:text>Keine GVP-Struktur vorhanden.</flux:text>
    @endif
</div>
</x-intranet-app-dokumente::dokumente-layout>
