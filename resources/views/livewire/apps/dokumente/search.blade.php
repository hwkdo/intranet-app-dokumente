<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Dokumente suchen" subheading="Titel und Kategorie durchsuchen.">
        <div class="space-y-4">
            <div class="glass-card sticky top-3 z-20 p-3" style="--glass-opacity: 0.92">
                <flux:input
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Suchbegriff eingeben…"
                    icon="magnifying-glass"
                    class="w-full"
                    aria-label="Dokumente durchsuchen"
                    clearable
                />
            </div>

            @if (empty($searchQuery) || strlen($searchQuery) < 2)
                <x-intranet-app-dokumente::hwk-empty icon="magnifying-glass" title="Suche starten">
                    Geben Sie mindestens 2 Zeichen ein, um die Suche zu starten.
                </x-intranet-app-dokumente::hwk-empty>
            @elseif ($this->results->isEmpty())
                <x-intranet-app-dokumente::hwk-empty icon="magnifying-glass" title="Keine Dokumente gefunden">
                    Versuchen Sie einen anderen Suchbegriff.
                </x-intranet-app-dokumente::hwk-empty>
            @else
                <x-intranet-app-dokumente::hwk-card
                    title="Suchergebnisse"
                    icon="magnifying-glass"
                    :description="$this->results->count().' Dokumente'"
                >
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Titel</flux:table.column>
                            <flux:table.column>Kategorie</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->results as $document)
                                <flux:table.row wire:key="search-doc-{{ $document->id }}">
                                    <flux:table.cell class="font-medium text-[#073070] dark:text-white">{{ $document->title }}</flux:table.cell>
                                    <flux:table.cell>{{ $document->category?->name ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button
                                            size="sm"
                                            :href="route('apps.dokumente.show', $document)"
                                            wire:navigate
                                        >
                                            Öffnen
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </x-intranet-app-dokumente::hwk-card>
            @endif
        </div>
    </x-intranet-app-dokumente::dokumente-layout>
</div>
