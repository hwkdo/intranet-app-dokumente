<div class="space-y-4">
    <x-intranet-app-dokumente::hwk-card title="Dokumente" icon="document-text" description="Alle Dokumente dieser App, einschließlich abgelaufener und gelöschter.">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <flux:switch wire:model.live="showExpiredOnly" label="Nur abgelaufene" />
                <flux:switch wire:model.live="showTrashed" label="Gelöschte anzeigen" />
            </div>
        </x-slot:actions>

        <div class="border-b border-[#d0e3f9] px-5 py-3 dark:border-white/10">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Suche…" icon="magnifying-glass" class="max-w-sm" aria-label="Dokumente durchsuchen" />
        </div>

        @if($this->documents->isEmpty())
            <x-intranet-app-dokumente::hwk-empty bare icon="document-text" title="Keine Dokumente">
                @if(trim($search) !== '' || $showExpiredOnly || $showTrashed)
                    Für die aktuelle Auswahl wurde kein Dokument gefunden.
                @else
                    Es sind noch keine Dokumente vorhanden.
                @endif
            </x-intranet-app-dokumente::hwk-empty>
        @else
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
                        <flux:table.row wire:key="admin-doc-{{ $doc->id }}">
                            <flux:table.cell class="font-medium text-[#073070] dark:text-white">{{ $doc->title }}</flux:table.cell>
                            <flux:table.cell>{{ $doc->category?->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if($doc->trashed())
                                    <flux:badge color="red">gelöscht</flux:badge>
                                @elseif($doc->isAbgelaufen())
                                    <flux:badge color="amber">abgelaufen</flux:badge>
                                    @if(! $doc->aktiv)
                                        <flux:badge class="ml-1">inaktiv</flux:badge>
                                    @endif
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

            <div class="border-t border-[#d0e3f9] px-5 py-3 dark:border-white/10">
                {{ $this->documents->links() }}
            </div>
        @endif
    </x-intranet-app-dokumente::hwk-card>

    @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
</div>
