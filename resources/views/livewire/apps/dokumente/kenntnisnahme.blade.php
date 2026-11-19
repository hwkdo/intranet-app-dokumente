<div>
    <x-intranet-app-dokumente::dokumente-layout
        heading="Kenntnisnahme"
        subheading="Ihre offenen und erledigten Kenntnisnahmen. Vorgesetzte sehen zusätzlich den Status ihrer Mitarbeitenden."
    >
        <div class="space-y-4">
            @if (session('message'))
                <section class="glass-card px-5 py-3 text-sm text-[#073070] dark:text-white">
                    {{ session('message') }}
                </section>
            @endif

            <div class="glass-card sticky top-3 z-20 space-y-3 p-3" style="--glass-opacity: 0.92">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Titel suchen…"
                    icon="magnifying-glass"
                    aria-label="Titel suchen"
                    clearable
                />
                <div class="flex flex-wrap gap-1.5" role="group" aria-label="Eigener Status">
                    @foreach (['all' => 'Alle', 'pending' => 'Offen', 'acknowledged' => 'Erfolgt'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('status', '{{ $value }}')"
                            @class([
                                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition',
                                'bg-[#073070] text-white shadow-md shadow-[#073070]/25' => $status === $value,
                                'bg-white/80 text-[#073070] ring-1 ring-[#d0e3f9] hover:bg-[#073070] hover:text-white dark:bg-white/10 dark:text-white dark:ring-white/15 dark:hover:bg-white dark:hover:text-[#073070]' => $status !== $value,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <x-intranet-app-dokumente::hwk-card
                title="Ihre Kenntnisnahme"
                icon="clipboard-document-check"
                description="Dokumente, die Sie zur Kenntnis nehmen oder bereits bestätigt haben."
            >
                @if ($this->documents->isEmpty())
                    <x-intranet-app-dokumente::hwk-empty bare icon="clipboard-document-check" title="Keine Dokumente">
                        @if (trim($search) !== '' || $status !== 'all')
                            Für diese Auswahl gibt es kein Dokument.
                        @else
                            Derzeit liegt kein Dokument zur Kenntnisnahme vor.
                        @endif
                    </x-intranet-app-dokumente::hwk-empty>
                @else
                    <div class="divide-y divide-[#d0e3f9] dark:divide-white/10">
                        @foreach ($this->documents as $document)
                            @php
                                $acknowledgment = $document->currentVersion?->acknowledgments->first();
                            @endphp
                            <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4" wire:key="kenntnisnahme-doc-{{ $document->id }}">
                                <div class="min-w-0">
                                    <p class="font-medium text-[#073070] dark:text-white">{{ $document->title }}</p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                                        {{ $document->category?->name ?? 'Ohne Kategorie' }}
                                        · Gültig bis {{ $document->gueltig_bis?->format('d.m.Y') ?? '—' }}
                                    </p>
                                    <div class="mt-2">
                                        @if ($acknowledgment)
                                            <flux:badge color="green" size="sm">Erfolgt</flux:badge>
                                            <span class="ms-2 text-xs text-slate-600 dark:text-white/70">
                                                {{ $acknowledgment->acknowledged_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                            </span>
                                        @else
                                            <flux:badge color="amber" size="sm">Offen</flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex max-w-full flex-wrap justify-end gap-2">
                                    @if ($document->currentVersion)
                                        <flux:button size="sm" :href="route('apps.dokumente.download', $document)" icon="arrow-down-tray" wire:click="markDownloaded({{ $document->id }})">Download</flux:button>
                                    @endif
                                    @if (! $acknowledgment)
                                        @if ($document->usesFormwerkAcknowledgment())
                                            <flux:button size="sm" variant="primary" icon="pencil-square" :href="route('apps.dokumente.formwerk', $document)">
                                                Per Unterschrift bestätigen
                                            </flux:button>
                                        @elseif ($this->hasDownloaded($document->id))
                                            <flux:button
                                                size="sm"
                                                variant="primary"
                                                icon="check-badge"
                                                :href="route('apps.dokumente.acknowledge', ['document' => $document, 'return' => 'kenntnisnahme'])"
                                            >Kenntnisnahme bestätigen</flux:button>
                                        @else
                                            <flux:button size="sm" variant="primary" icon="check-badge" disabled title="Bitte zuerst das Dokument herunterladen">
                                                Kenntnisnahme bestätigen
                                            </flux:button>
                                            <p class="w-full text-sm text-slate-600 dark:text-white/70">
                                                Bitte laden Sie das Dokument zuerst herunter, bevor Sie die Kenntnisnahme bestätigen.
                                            </p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-[#d0e3f9] px-5 py-3 dark:border-white/10">
                        {{ $this->documents->links() }}
                    </div>
                @endif
            </x-intranet-app-dokumente::hwk-card>

            @if ($this->leadership['canViewTeam'])
                <x-intranet-app-dokumente::hwk-card
                    title="Mitarbeitende"
                    icon="users"
                    :description="$this->leadership['scopeChoice']
                        ? 'Direkte Mitarbeitende gehören zu '.$this->leadership['label'].'. Alle Mitarbeitenden schließen die untergeordneten Einheiten ein.'
                        : 'Mitarbeitende von '.$this->leadership['label'].'.'"
                >
                    <x-slot:actions>
                        @if ($this->leadership['scopeChoice'])
                            <div class="flex flex-wrap gap-1.5" role="group" aria-label="Mitarbeitendenkreis">
                                <button
                                    type="button"
                                    wire:click="$set('teamScope', 'direct')"
                                    @class([
                                        'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition',
                                        'bg-[#073070] text-white shadow-md shadow-[#073070]/25' => $teamScope === 'direct',
                                        'bg-white/80 text-[#073070] ring-1 ring-[#d0e3f9] hover:bg-[#073070] hover:text-white dark:bg-white/10 dark:text-white dark:ring-white/15 dark:hover:bg-white dark:hover:text-[#073070]' => $teamScope !== 'direct',
                                    ])
                                >Direkte Mitarbeitende</button>
                                <button
                                    type="button"
                                    wire:click="$set('teamScope', 'all')"
                                    @class([
                                        'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition',
                                        'bg-[#073070] text-white shadow-md shadow-[#073070]/25' => $teamScope === 'all',
                                        'bg-white/80 text-[#073070] ring-1 ring-[#d0e3f9] hover:bg-[#073070] hover:text-white dark:bg-white/10 dark:text-white dark:ring-white/15 dark:hover:bg-white dark:hover:text-[#073070]' => $teamScope !== 'all',
                                    ])
                                >Alle Mitarbeitenden</button>
                            </div>
                        @endif
                    </x-slot:actions>

                    <div class="border-b border-[#d0e3f9] px-5 py-3 dark:border-white/10">
                        <flux:input
                            wire:model.live.debounce.300ms="teamSearch"
                            placeholder="Name suchen…"
                            icon="magnifying-glass"
                            aria-label="Mitarbeitende suchen"
                            clearable
                        />
                    </div>

                    @if ($this->teamMembers->isEmpty())
                        <x-intranet-app-dokumente::hwk-empty bare icon="users" title="Keine Mitarbeitenden">
                            In dieser Auswahl gibt es keine Mitarbeitenden mit Zugang zur Dokumente-App.
                        </x-intranet-app-dokumente::hwk-empty>
                    @else
                        <div class="divide-y divide-[#d0e3f9] dark:divide-white/10">
                            @foreach ($this->teamMembers as $member)
                                @php
                                    $counts = $this->teamCounts[$member->id] ?? ['open' => 0, 'done' => 0];
                                    $unit = $member->gvp ? trim($member->gvp->kuerzel.' '.$member->gvp->nummer.' '.$member->gvp->name) : '—';
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4" wire:key="kenntnisnahme-member-{{ $member->id }}">
                                    <div class="min-w-0">
                                        <p class="font-medium text-[#073070] dark:text-white">{{ trim($member->vorname.' '.$member->nachname) }}</p>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-white/70">{{ $unit }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="text-sm text-slate-600 dark:text-white/70">
                                            Offen
                                            @if ($counts['open'] > 0)
                                                <span class="ms-1 inline-flex rounded-full bg-red-600 px-1.5 text-xs font-semibold text-white">{{ $counts['open'] }}</span>
                                            @else
                                                <span class="ms-1">0</span>
                                            @endif
                                        </span>
                                        <span class="text-sm text-slate-600 dark:text-white/70">Erfolgt {{ $counts['done'] }}</span>
                                        <flux:button size="sm" wire:click="openEmployee({{ $member->id }})">Status</flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t border-[#d0e3f9] px-5 py-3 dark:border-white/10">
                            {{ $this->teamMembers->links() }}
                        </div>
                    @endif
                </x-intranet-app-dokumente::hwk-card>
            @endif
        </div>

        <flux:modal wire:model="showEmployeeDetail" class="max-w-3xl">
            @if ($this->selectedEmployee)
                <div class="space-y-4">
                    <div class="pe-10">
                        <h2 class="text-lg font-semibold text-[#073070] dark:text-white">
                            {{ trim($this->selectedEmployee->vorname.' '.$this->selectedEmployee->nachname) }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                            Status der Kenntnisnahmen
                            @if ($this->selectedEmployee->gvp)
                                · {{ trim($this->selectedEmployee->gvp->kuerzel.' '.$this->selectedEmployee->gvp->nummer.' '.$this->selectedEmployee->gvp->name) }}
                            @endif
                        </p>
                    </div>

                    @if ($this->selectedEmployeeDocuments === [])
                        <x-intranet-app-dokumente::hwk-empty bare icon="document-text" title="Keine Dokumente">
                            Für diese Person liegt kein Dokument zur Kenntnisnahme vor.
                        </x-intranet-app-dokumente::hwk-empty>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Titel</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($this->selectedEmployeeDocuments as $row)
                                    <flux:table.row wire:key="kenntnisnahme-employee-doc-{{ $row['document']->id }}">
                                        <flux:table.cell class="font-medium text-[#073070] dark:text-white">{{ $row['document']->title }}</flux:table.cell>
                                        <flux:table.cell>
                                            @if ($row['acknowledged'])
                                                <flux:badge color="green" size="sm">Erfolgt</flux:badge>
                                                @if ($row['acknowledged_at'])
                                                    <span class="mt-1 block text-xs text-slate-600 dark:text-white/70">
                                                        {{ $row['acknowledged_at']->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                                    </span>
                                                @endif
                                            @else
                                                <flux:badge color="amber" size="sm">Offen</flux:badge>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif

                    <div class="flex justify-end">
                        <flux:button variant="ghost" wire:click="closeEmployee">Schließen</flux:button>
                    </div>
                </div>
            @endif
        </flux:modal>
    </x-intranet-app-dokumente::dokumente-layout>
</div>
