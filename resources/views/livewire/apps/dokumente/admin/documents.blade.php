<div class="space-y-4">
    <flux:card class="glass-card space-y-4">
        <div class="flex flex-wrap items-center gap-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Suche…" icon="magnifying-glass" class="max-w-sm" />
            <flux:switch wire:model.live="showTrashed" label="Gelöschte anzeigen" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Titel</flux:table.column>
                <flux:table.column>Kategorie</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Gültig bis</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->documents as $doc)
                    <flux:table.row>
                        <flux:table.cell>{{ $doc->title }}</flux:table.cell>
                        <flux:table.cell>{{ $doc->category?->name }}</flux:table.cell>
                        <flux:table.cell>
                            @if($doc->trashed())
                                <flux:badge color="red">gelöscht</flux:badge>
                            @elseif(! $doc->aktiv)
                                <flux:badge>inaktiv</flux:badge>
                            @else
                                <flux:badge color="green">aktiv</flux:badge>
                            @endif
                            @if($doc->requires_acknowledgment)
                                <flux:badge class="ml-1">Kenntnisnahme</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $doc->gueltig_bis?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap justify-end gap-2">
                                @if($doc->requires_acknowledgment && ! $doc->trashed())
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
</div>
