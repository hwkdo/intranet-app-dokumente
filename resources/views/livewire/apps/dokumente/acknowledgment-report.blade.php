<div>
    <flux:modal wire:model="showModal" wire:close="close" class="max-w-5xl">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3 pe-16">
                <div>
                    <h2 class="text-lg font-semibold text-[#073070] dark:text-white">Kenntnisnahme-Auswertung</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                        {{ $this->document->title }}
                        @if($this->document->currentVersion)
                            · Version {{ $this->document->currentVersion->version_number }}
                        @endif
                    </p>
                </div>
                <flux:button wire:click="exportExcel" icon="arrow-down-tray" variant="primary">
                    Excel exportieren
                </flux:button>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <section class="rounded-xl bg-[#d0e3f9]/40 px-4 py-3 ring-1 ring-[#d0e3f9] dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs font-medium text-slate-600 dark:text-white/70">Gesamt</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-[#073070] dark:text-white">{{ $this->counts['total'] }}</p>
                </section>
                <section class="rounded-xl bg-[#d0e3f9]/40 px-4 py-3 ring-1 ring-[#d0e3f9] dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs font-medium text-slate-600 dark:text-white/70">Erfolgt</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $this->counts['acknowledged'] }}</p>
                </section>
                <section class="rounded-xl bg-[#d0e3f9]/40 px-4 py-3 ring-1 ring-[#d0e3f9] dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs font-medium text-slate-600 dark:text-white/70">Nicht erfolgt</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-amber-700 dark:text-amber-300">{{ $this->counts['pending'] }}</p>
                </section>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filter">
                    @foreach(['all' => 'Alle', 'acknowledged' => 'Nur erfolgt', 'pending' => 'Nur nicht erfolgt'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $value }}')"
                            @class([
                                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition',
                                'bg-[#073070] text-white shadow-md shadow-[#073070]/25' => $statusFilter === $value,
                                'bg-white/80 text-[#073070] ring-1 ring-[#d0e3f9] hover:bg-[#073070] hover:text-white dark:bg-white/10 dark:text-white dark:ring-white/15 dark:hover:bg-white dark:hover:text-[#073070]' => $statusFilter !== $value,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <flux:input
                    wire:model.live.debounce.300ms="userSearch"
                    placeholder="Vorname oder Nachname…"
                    icon="magnifying-glass"
                    class="sm:max-w-xs"
                    aria-label="Benutzer suchen"
                />
            </div>

            @if($this->rows->isEmpty())
                <x-intranet-app-dokumente::hwk-empty bare icon="users" title="Keine Benutzer">
                    Keine Benutzer für die aktuelle Auswahl.
                </x-intranet-app-dokumente::hwk-empty>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nachname</flux:table.column>
                        <flux:table.column>Vorname</flux:table.column>
                        <flux:table.column>E-Mail</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Kenntnisnahme am</flux:table.column>
                        <flux:table.column>PDF</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->rows as $row)
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
                                <flux:table.cell>
                                    @if($row->signed_pdf_url)
                                        <flux:button
                                            :href="$row->signed_pdf_url"
                                            size="xs"
                                            variant="ghost"
                                            icon="arrow-down-tray"
                                        >
                                            PDF
                                        </flux:button>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif

            <div>
                {{ $this->rows->links() }}
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="close" variant="ghost">Schließen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
