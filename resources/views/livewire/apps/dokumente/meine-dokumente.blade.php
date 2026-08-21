<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Meine Dokumente" subheading="Hochgeladen oder verantwortlich">
        <flux:card class="glass-card space-y-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Suche nach Titel…" icon="magnifying-glass" />

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
                        <flux:table.row>
                            <flux:table.cell>{{ $doc->title }}</flux:table.cell>
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
                                    <flux:button size="sm" wire:click="openDetail({{ $doc->id }})">Öffnen</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            {{ $this->documents->links() }}
        </flux:card>

        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    </x-intranet-app-dokumente::dokumente-layout>
</div>
