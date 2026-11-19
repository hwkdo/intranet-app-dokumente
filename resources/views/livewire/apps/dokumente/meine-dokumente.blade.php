<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Meine Dokumente" subheading="Dokumente, die Sie hochgeladen haben oder für die Sie verantwortlich sind.">
        <div class="space-y-4">
            <div class="glass-card sticky top-3 z-20 p-3" style="--glass-opacity: 0.92">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Suche nach Titel…"
                    icon="magnifying-glass"
                    aria-label="Eigene Dokumente durchsuchen"
                    clearable
                />
            </div>

            <x-intranet-app-dokumente::hwk-card
                title="Ihre Dokumente"
                icon="document-text"
                description="Hochgeladen oder verantwortlich"
            >
                @if ($this->documents->isEmpty())
                    <x-intranet-app-dokumente::hwk-empty bare icon="document-text" title="Keine Dokumente">
                        @if (trim($search) !== '')
                            Für „{{ $search }}“ wurde kein Dokument gefunden.
                        @else
                            Sie haben noch kein Dokument hochgeladen und sind für keines verantwortlich.
                        @endif
                    </x-intranet-app-dokumente::hwk-empty>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Titel</flux:table.column>
                            <flux:table.column>Kategorie</flux:table.column>
                            <flux:table.column>Gültig bis</flux:table.column>
                            <flux:table.column>Rolle</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->documents as $doc)
                                <flux:table.row wire:key="meine-doc-{{ $doc->id }}">
                                    <flux:table.cell class="font-medium text-[#073070] dark:text-white">{{ $doc->title }}</flux:table.cell>
                                    <flux:table.cell>{{ $doc->category?->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $doc->gueltig_bis?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if((int) $doc->uploader_id === (int) auth()->id()) Uploader @endif
                                        @if((int) $doc->responsible_id === (int) auth()->id()) Responsible @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            @if($doc->requires_acknowledgment)
                                                <flux:button size="sm" icon="clipboard-document-check" wire:click="openAcknowledgmentReport({{ $doc->id }})">
                                                    Kenntnisnahme
                                                </flux:button>
                                            @endif
                                            @can('update', $doc)
                                                <flux:button size="sm" icon="pencil-square" wire:click="openEditModal({{ $doc->id }})">Bearbeiten</flux:button>
                                            @endcan
                                            <flux:button size="sm" wire:click="openDetail({{ $doc->id }})">Öffnen</flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="border-t border-[#d0e3f9] px-5 py-3 dark:border-white/10">
                        {{ $this->documents->links() }}
                    </div>
                @endif
            </x-intranet-app-dokumente::hwk-card>
        </div>

        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    </x-intranet-app-dokumente::dokumente-layout>
</div>
