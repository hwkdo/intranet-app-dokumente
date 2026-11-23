<div class="space-y-4">
    <x-intranet-app-dokumente::hwk-card title="Berechtigungen" icon="shield-check" description="Welche Permission für Upload, Kenntnisnahme und GVP-Auswahl gilt." padded>
        <p class="text-sm text-slate-600 dark:text-white/70">
            Die App prüft Spatie-Permissions. Rollen werden über
            <code class="text-sm text-[#073070] dark:text-white">php artisan intranet-app:sync-permissions --app=dokumente</code>
            angelegt und können hier Nutzern zugewiesen werden. Unten legen Sie fest, welche Permission-Namen
            für Upload, Kenntnisnahme und GVP-Auswahl gelten — und sehen, welche Rollen diese aktuell haben.
        </p>
    </x-intranet-app-dokumente::hwk-card>

    <x-intranet-app-dokumente::hwk-card title="Empfohlene Standard-Rollen" icon="information-circle" description="Jede Rolle enthält die Permissions, die für ihre Aufgabe nötig sind." padded>
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-white/70">
            <li><strong class="text-[#073070] dark:text-white">App-Dokumente-Benutzer</strong> — Dokumente sehen. Aktive Benutzer erhalten diese Rolle beim Permission-Sync automatisch.</li>
            <li><strong class="text-[#073070] dark:text-white">App-Dokumente-Upload</strong> — Dokumente hochladen (ohne Kenntnisnahme-Flag und ohne manuelle GVP-Wahl)</li>
            <li><strong class="text-[#073070] dark:text-white">App-Dokumente-Kenntnisnahme</strong> — hochladen und zusätzlich „Zur Kenntnisnahme“ setzen</li>
            <li><strong class="text-[#073070] dark:text-white">App-Dokumente-GVP-Auswahl</strong> — hochladen und zusätzlich die GVP manuell wählen</li>
            <li><strong class="text-[#073070] dark:text-white">App-Dokumente-Admin</strong> — volle Verwaltung inkl. aller Capabilities</li>
        </ul>
        <p class="mt-3 text-sm text-slate-600 dark:text-white/70">
            Kenntnisnahme und GVP-Auswahl beinhalten jeweils auch die Upload-Permission, weil beide Funktionen nur im Upload-/Aktualisierungsdialog sinnvoll sind.
        </p>
    </x-intranet-app-dokumente::hwk-card>

    <form wire:submit="save" class="space-y-4">
        <x-intranet-app-dokumente::hwk-card title="Upload" icon="arrow-up-tray" description="Wer neue Dokumente einstellen darf." padded>
            <x-slot:actions>
                <flux:button type="button" size="sm" icon="users" wire:click="openUserManager('uploader')">
                    Benutzer verwalten
                </flux:button>
            </x-slot:actions>
            <flux:input
                wire:model.live.debounce.300ms="permissionUpload"
                label="Permission"
                description="Standard: {{ \Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::defaultUpload() }}"
                required
            />
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-[#073070] dark:text-white">Rollen mit dieser Permission:</p>
                @forelse($this->uploadRoles as $roleName)
                    <flux:badge>{{ $roleName }}</flux:badge>
                @empty
                    <p class="text-sm text-slate-600 dark:text-white/70">Keine Rolle gefunden (Permission ggf. noch nicht synchronisiert oder ohne Rollenzuweisung).</p>
                @endforelse
            </div>
        </x-intranet-app-dokumente::hwk-card>

        <x-intranet-app-dokumente::hwk-card title="Zur Kenntnisnahme" icon="check-badge" description="Wer beim Hochladen/Aktualisieren „Zur Kenntnisnahme“ aktivieren darf (Standard-Rolle enthält auch Upload)." padded>
            <x-slot:actions>
                <flux:button type="button" size="sm" icon="users" wire:click="openUserManager('kenntnisnahme')">
                    Benutzer verwalten
                </flux:button>
            </x-slot:actions>
            <flux:input
                wire:model.live.debounce.300ms="permissionKenntnisnahme"
                label="Permission"
                description="Standard: {{ \Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::defaultKenntnisnahme() }}"
                required
            />
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-[#073070] dark:text-white">Rollen mit dieser Permission:</p>
                @forelse($this->kenntnisnahmeRoles as $roleName)
                    <flux:badge>{{ $roleName }}</flux:badge>
                @empty
                    <p class="text-sm text-slate-600 dark:text-white/70">Keine Rolle gefunden (Permission ggf. noch nicht synchronisiert oder ohne Rollenzuweisung).</p>
                @endforelse
            </div>
        </x-intranet-app-dokumente::hwk-card>

        <x-intranet-app-dokumente::hwk-card title="GVP-Auswahl" icon="building-office-2" description="Wer beim Upload die GVP manuell wählen darf (sonst gilt die GVP des Verantwortlichen; Standard-Rolle enthält auch Upload)." padded>
            <x-slot:actions>
                <flux:button type="button" size="sm" icon="users" wire:click="openUserManager('gvp_chooser')">
                    Benutzer verwalten
                </flux:button>
            </x-slot:actions>
            <flux:input
                wire:model.live.debounce.300ms="permissionChooseGvp"
                label="Permission"
                description="Standard: {{ \Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::defaultChooseGvp() }}"
                required
            />
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-[#073070] dark:text-white">Rollen mit dieser Permission:</p>
                @forelse($this->chooseGvpRoles as $roleName)
                    <flux:badge>{{ $roleName }}</flux:badge>
                @empty
                    <p class="text-sm text-slate-600 dark:text-white/70">Keine Rolle gefunden (Permission ggf. noch nicht synchronisiert oder ohne Rollenzuweisung).</p>
                @endforelse
            </div>
        </x-intranet-app-dokumente::hwk-card>

        <div class="sticky bottom-4 z-30 flex justify-end">
            <div class="glass-card flex items-center gap-3 px-4 py-2.5 shadow-lg shadow-[#073070]/15" style="--glass-opacity: 0.94">
                <flux:button type="submit" variant="primary">Berechtigungen speichern</flux:button>
            </div>
        </div>
    </form>

    <flux:modal wire:model="showUserModal" class="max-w-2xl">
        <div class="space-y-4">
            <div class="pe-10">
                <h2 class="text-lg font-semibold text-[#073070] dark:text-white">Benutzer verwalten</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                    Rolle <strong class="text-[#073070] dark:text-white">{{ $managingRoleName }}</strong>
                    @if($managingRoleKey !== '')
                        ({{ $this->managingRoleLabel() }})
                    @endif
                    — ausgewählte Benutzer erscheinen oben in der Liste.
                </p>
            </div>

            <flux:select
                wire:model.live="selectedUserIds"
                variant="listbox"
                multiple
                searchable
                clear="close"
                indicator="checkbox"
                label="Benutzer"
                placeholder="Benutzer suchen und auswählen…"
                selected-suffix="ausgewählt"
            >
                @foreach($this->usersForRoleSelect as $user)
                    <flux:select.option wire:key="role-user-{{ $user->id }}" value="{{ $user->id }}">
                        {{ $user->label }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeUserManager">Abbrechen</flux:button>
                <flux:button type="button" variant="primary" wire:click="saveRoleUsers">Zuweisungen speichern</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
