<div @class(['space-y-4']) @if($this->hasOpenWork) wire:poll.15s @endif>
    <flux:card class="glass-card space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <flux:heading size="lg">LightRAG</flux:heading>
                <flux:text class="mt-1">Neue und aktualisierte Dokumente gehen von allein in die Instanz Dokumente. Fehler bleiben hier stehen.</flux:text>
            </div>
            <flux:button variant="primary" icon="arrow-up-tray" wire:click="enqueueAll" wire:loading.attr="disabled">
                Alle indexieren
            </flux:button>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Suche…" icon="magnifying-glass" class="max-w-sm" />
            <flux:select wire:model.live="filter" variant="listbox" class="max-w-xs">
                <flux:select.option value="all">Alle</flux:select.option>
                <flux:select.option value="working">In Arbeit</flux:select.option>
                <flux:select.option value="indexed">In LightRAG</flux:select.option>
                <flux:select.option value="failed">Fehlgeschlagen</flux:select.option>
                <flux:select.option value="missing">Nicht indexiert</flux:select.option>
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Dokument</flux:table.column>
                <flux:table.column>Fassung</flux:table.column>
                <flux:table.column>LightRAG</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($this->documents as $document)
                    @php
                        $state = $document->lightRagState;
                        $current = $state !== null && (int) $state->document_version_id === (int) $document->current_version_id;
                    @endphp
                    <flux:table.row wire:key="lightrag-document-{{ $document->id }}">
                        <flux:table.cell>{{ $document->title }}</flux:table.cell>
                        <flux:table.cell>{{ $document->currentVersion?->version_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if(! $current || $state === null)
                                <flux:badge>nicht indexiert</flux:badge>
                            @elseif($state->status === \Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus::Processed)
                                <flux:badge color="green">{{ $state->status->label() }}</flux:badge>
                            @elseif($state->status === \Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus::Failed)
                                <flux:badge color="red">{{ $state->status->label() }}</flux:badge>
                            @else
                                <flux:badge color="amber">{{ $state->status->label() }}</flux:badge>
                            @endif
                            @if($current && $state?->indexed_at)
                                <flux:text class="mt-1 text-xs">{{ $state->indexed_at->format('d.m.Y H:i') }}</flux:text>
                            @endif
                            @if($current && $state?->status === \Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus::Failed && $state->error_message)
                                <flux:text class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $state->error_message }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if(! $current || $state === null || $state->status === \Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus::Failed)
                                <flux:button size="sm" wire:click="retry({{ $document->id }})" wire:loading.attr="disabled">
                                    Erneut versuchen
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">Keine Dokumente.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div>
            {{ $this->documents->links() }}
        </div>
    </flux:card>
</div>
