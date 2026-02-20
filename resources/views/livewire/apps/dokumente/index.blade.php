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
            @can('upload-app-dokumente')
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
                [data-dokumente-matrix] th[data-flux-column]:not(:first-child):not(:last-child) {
                    max-width: 140px;
                    width: 140px;
                    white-space: normal;
                    word-break: break-word;
                }
                [data-dokumente-matrix] th[data-flux-column]:not(:first-child):not(:last-child) div {
                    white-space: normal;
                    word-break: break-word;
                }
                [x-cloak] { display: none !important; }
            </style>
            <flux:table class="w-full" data-dokumente-matrix>
                <flux:table.columns>
                    <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! whitespace-nowrap"></flux:table.column>
                    @foreach($categories as $cat)
                        <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! text-center w-[140px] max-w-[140px]" align="center">
                            <span class="block w-full text-center whitespace-normal break-words hyphens-auto text-sm">{{ $cat->name }}</span>
                        </flux:table.column>
                    @endforeach
                    <flux:table.column class="bg-[#073070]! dark:bg-[#04214e]! text-white! text-center w-12">∑</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    {{-- HGF row --}}
                    <flux:table.row>
                        @php $hgfIds = implode(',', $hgf->getDescendantIds()); $m = $this->countMatrix['hgf']; @endphp
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium">
                            {{ $hgf->bezeichnung }}
                        </flux:table.cell>
                        @foreach($categories as $cat)
                            @php $cnt = $m[$cat->id] ?? 0; @endphp
                            <flux:table.cell class="bg-white/50 dark:bg-[#04214e]/40 text-center">
                                @if($cnt > 0)
                                    <button type="button" wire:click="openDocumentListModal({{ $cat->id }}, '{{ $hgfIds }}', '{{ addslashes($cat->name) }}', '{{ addslashes($hgf->bezeichnung) }}')" class="text-blue-600 hover:underline">
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
                                <button type="button" wire:click="openDocumentListModal(null, '{{ $hgfIds }}', 'Alle', '{{ addslashes($hgf->bezeichnung) }}')" class="text-white! hover:underline">
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
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium cursor-pointer select-none" @click="toggleStab()">
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
                                <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white!">{{ $stab->bezeichnung }}</flux:table.cell>
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
                            <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium cursor-pointer select-none" @click="toggleGb({{ $gb->id }})">
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
                                <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white!">Geschäftsführung</flux:table.cell>
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
                                    <flux:table.cell class="bg-[#456494] dark:bg-[#456494]/80 text-white!">{{ $abt->bezeichnung }}</flux:table.cell>
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
                        <flux:table.cell class="bg-[#073070] dark:bg-[#04214e] text-white! font-medium">∑</flux:table.cell>
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
                            <flux:table.row>
                                <flux:table.cell>
                                    @if($doc->hasMedia('document') && $doc->getFirstMedia('document')->hasGeneratedConversion('thumb'))
                                        <img src="{{ $doc->getFirstMedia('document')->getUrl('thumb') }}" alt="" class="h-12 w-auto object-contain" />
                                    @else
                                        <flux:icon name="document" class="size-12 text-zinc-400" />
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $doc->title }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="sm" href="{{ route('apps.dokumente.download', $doc) }}" target="_blank" variant="primary">Download</flux:button>
                                    @can('manage-app-dokumente')
                                        <flux:button size="sm" wire:click="openEditModal({{ $doc->id }})" variant="ghost">Bearbeiten</flux:button>
                                    @endcan
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

        @can('upload-app-dokumente')
            <flux:modal wire:model="showUploadModal" name="upload-document" class="md:max-w-2xl">
                <form wire:submit="saveUpload" class="space-y-4">
                    <flux:heading size="lg" class="mb-4">Dokument hochladen</flux:heading>
                    <flux:input wire:model="uploadTitle" label="Titel" required />
                    <flux:textarea wire:model="uploadDescription" label="Beschreibung" rows="3" />
                    <flux:input wire:model="uploadGueltigBis" type="date" label="Gültig bis (leer = unbegrenzt)" />
                    <flux:checkbox wire:model="uploadAktiv" label="Aktiv" />
                    <flux:select wire:model.live="uploadResponsibleId" label="Verantwortliche/r" placeholder="Verantwortliche/n wählen" required>
                        @foreach($this->usersForSelect as $id => $label)
                            <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="uploadCategoryId" label="Kategorie" placeholder="Kategorie wählen" required>
                        @foreach($this->categories as $cat)
                            <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="file" wire:model="uploadFile" label="Datei" required />
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

        @can('manage-app-dokumente')
            <flux:modal wire:model="showEditModal" name="edit-document" class="md:max-w-2xl">
                <form wire:submit="saveEdit" class="space-y-4">
                    <flux:heading size="lg" class="mb-4">Dokument bearbeiten</flux:heading>
                    <flux:input wire:model="editTitle" label="Titel" required />
                    <flux:textarea wire:model="editDescription" label="Beschreibung" rows="3" />
                    <flux:input wire:model="editGueltigBis" type="date" label="Gültig bis (leer = unbegrenzt)" />
                    <flux:checkbox wire:model="editAktiv" label="Aktiv" />
                    <flux:select wire:model="editResponsibleId" label="Verantwortliche/r" placeholder="Verantwortliche/n wählen" required>
                        @foreach($this->usersForSelect as $id => $label)
                            <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="editCategoryId" label="Kategorie" placeholder="Kategorie wählen" required>
                        @foreach($this->categories as $cat)
                            <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="file" wire:model="editFile" label="Neue Datei (optional, ersetzt bestehende)" />
                    @error('editFile')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                    <div class="flex gap-2 pt-4">
                        <flux:button type="submit" variant="primary">Speichern</flux:button>
                        <flux:button type="button" wire:click="closeEditModal" variant="ghost">Abbrechen</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcan
    @else
        <flux:text>Keine GVP-Struktur vorhanden.</flux:text>
    @endif
</div>
</x-intranet-app-dokumente::dokumente-layout>
