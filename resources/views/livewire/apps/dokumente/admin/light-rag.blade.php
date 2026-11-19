@php
    $filters = [
        'all' => 'Alle',
        'working' => 'In Arbeit',
        'indexed' => 'In LightRAG',
        'failed' => 'Fehlgeschlagen',
        'missing' => 'Nicht indexiert',
    ];
@endphp

<div @class(['space-y-4']) @if($this->hasOpenWork) wire:poll.15s @endif>
    <x-intranet-app-dokumente::hwk-card
        title="LightRAG"
        icon="circle-stack"
        description="Neue und aktualisierte Dokumente gehen von allein in die Instanz Dokumente. Fehler bleiben hier stehen."
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="arrow-up-tray" wire:click="enqueueAll" wire:loading.attr="disabled">
                Alle indexieren
            </flux:button>
        </x-slot:actions>

        <div class="space-y-3 border-b border-[#d0e3f9] px-5 py-3 dark:border-white/10">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Suche…" icon="magnifying-glass" class="max-w-sm" aria-label="Index durchsuchen" />
            <div class="flex flex-wrap gap-1.5">
                @foreach($filters as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('filter', '{{ $value }}')"
                        @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition',
                            'bg-[#073070] text-white shadow-md shadow-[#073070]/25' => $filter === $value,
                            'bg-white/80 text-[#073070] ring-1 ring-[#d0e3f9] hover:bg-[#073070] hover:text-white dark:bg-white/10 dark:text-white dark:ring-white/15 dark:hover:bg-white dark:hover:text-[#073070]' => $filter !== $value,
                        ])
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if($this->documents->isEmpty())
            <x-intranet-app-dokumente::hwk-empty bare icon="circle-stack" title="Keine Dokumente">
                Für die aktuelle Auswahl ist kein Dokument vorhanden.
            </x-intranet-app-dokumente::hwk-empty>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Dokument</flux:table.column>
                    <flux:table.column>Fassung</flux:table.column>
                    <flux:table.column>LightRAG</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->documents as $document)
                        @php
                            $state = $document->lightRagState;
                            $current = $state !== null && (int) $state->document_version_id === (int) $document->current_version_id;
                        @endphp
                        <flux:table.row wire:key="lightrag-document-{{ $document->id }}">
                            <flux:table.cell class="font-medium text-[#073070] dark:text-white">{{ $document->title }}</flux:table.cell>
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
                                    <p class="mt-1 text-xs text-slate-600 dark:text-white/70">{{ $state->indexed_at->format('d.m.Y H:i') }}</p>
                                @endif
                                @if($current && $state?->status === \Hwkdo\IntranetAppDokumente\Enums\DocumentLightRagStatus::Failed && $state->error_message)
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $state->error_message }}</p>
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
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="border-t border-[#d0e3f9] px-5 py-3 dark:border-white/10">
                {{ $this->documents->links() }}
            </div>
        @endif
    </x-intranet-app-dokumente::hwk-card>
</div>
