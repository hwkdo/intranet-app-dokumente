<div>
    <flux:modal wire:model="showModal" class="max-w-5xl">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">Kenntnisnahme-Auswertung</flux:heading>
                    <flux:text class="mt-1">
                        {{ $this->document->title }}
                        @if($this->document->currentVersion)
                            · Version {{ $this->document->currentVersion->version_number }}
                        @endif
                    </flux:text>
                </div>
                <flux:button wire:click="exportExcel" icon="arrow-down-tray" variant="primary">
                    Excel exportieren
                </flux:button>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:badge>Gesamt: {{ $this->counts['total'] }}</flux:badge>
                <flux:badge color="green">Erfolgt: {{ $this->counts['acknowledged'] }}</flux:badge>
                <flux:badge color="amber">Nicht erfolgt: {{ $this->counts['pending'] }}</flux:badge>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <flux:radio.group wire:model.live="statusFilter" variant="segmented" label="Filter">
                    <flux:radio value="all">Alle</flux:radio>
                    <flux:radio value="acknowledged">Nur erfolgt</flux:radio>
                    <flux:radio value="pending">Nur nicht erfolgt</flux:radio>
                </flux:radio.group>

                <flux:input
                    wire:model.live.debounce.300ms="userSearch"
                    placeholder="Vorname oder Nachname…"
                    icon="magnifying-glass"
                    class="sm:max-w-xs"
                />
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nachname</flux:table.column>
                    <flux:table.column>Vorname</flux:table.column>
                    <flux:table.column>E-Mail</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Kenntnisnahme am</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->rows as $row)
                        <flux:table.row wire:key="ack-row-{{ $row->user_id }}">
                            <flux:table.cell>{{ $row->nachname }}</flux:table.cell>
                            <flux:table.cell>{{ $row->vorname }}</flux:table.cell>
                            <flux:table.cell>{{ $row->email }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->acknowledged)
                                    <flux:badge color="green">Erfolgt</flux:badge>
                                @else
                                    <flux:badge color="amber">Nicht erfolgt</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->acknowledged_at)
                                    {{ \Illuminate\Support\Carbon::parse($row->acknowledged_at)->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">Keine Benutzer für die aktuelle Auswahl.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div>
                {{ $this->rows->links() }}
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="close" variant="ghost">Schließen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
