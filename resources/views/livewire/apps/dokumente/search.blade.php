<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Dokumente" subheading="Suche">
        <flux:card class="glass-card space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">Dokumente durchsuchen</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Geben Sie einen Suchbegriff ein, um Dokumente zu finden.
                </flux:text>
            </div>

            <flux:input
                wire:model.live.debounce.300ms="searchQuery"
                placeholder="Suchbegriff eingeben…"
                icon="magnifying-glass"
                class="w-full"
            />

            @if (! empty($searchQuery) && strlen($searchQuery) >= 2)
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:heading size="md">Suchergebnisse</flux:heading>
                        <flux:badge variant="outline">{{ $this->results->count() }}</flux:badge>
                    </div>

                    @if ($this->results->isEmpty())
                        <flux:callout variant="info">
                            Keine Dokumente gefunden. Versuchen Sie einen anderen Suchbegriff.
                        </flux:callout>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Titel</flux:table.column>
                                <flux:table.column>Kategorie</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($this->results as $document)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $document->title }}</flux:table.cell>
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
                    @endif
                </div>
            @elseif (empty($searchQuery))
                <flux:callout variant="info">
                    Geben Sie mindestens 2 Zeichen ein, um die Suche zu starten.
                </flux:callout>
            @endif
        </flux:card>
    </x-intranet-app-dokumente::dokumente-layout>
</div>
