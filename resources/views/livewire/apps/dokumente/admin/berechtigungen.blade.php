<div class="space-y-4">
    <flux:card class="glass-card space-y-4">
        <div>
            <flux:heading size="lg">Berechtigungen</flux:heading>
            <flux:text class="mt-1">
                Die App prüft Spatie-Permissions. Rollen werden über
                <code class="text-sm">php artisan intranet-app:sync-permissions --app=dokumente</code>
                angelegt und können hier Nutzern zugewiesen werden. Unten legen Sie fest, welche Permission-Namen
                für Upload, Kenntnisnahme und GVP-Auswahl gelten — und sehen, welche Rollen diese aktuell haben.
            </flux:text>
        </div>

        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.heading>Empfohlene Standard-Rollen</flux:callout.heading>
            <flux:callout.text>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    <li><strong>App-Dokumente-Upload</strong> — Dokumente hochladen (ohne Kenntnisnahme-Flag und ohne manuelle GVP-Wahl)</li>
                    <li><strong>App-Dokumente-Kenntnisnahme</strong> — hochladen und zusätzlich „Zur Kenntnisnahme“ setzen</li>
                    <li><strong>App-Dokumente-GVP-Auswahl</strong> — hochladen und zusätzlich die GVP manuell wählen</li>
                    <li><strong>App-Dokumente-Admin</strong> — volle Verwaltung inkl. aller Capabilities</li>
                </ul>
                <p class="mt-2 text-sm">
                    Kenntnisnahme und GVP-Auswahl beinhalten jeweils auch die Upload-Permission, weil beide Funktionen nur im Upload-/Aktualisierungsdialog sinnvoll sind.
                </p>
            </flux:callout.text>
        </flux:callout>

        <form wire:submit="save" class="space-y-6">
            <div class="space-y-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="md">Upload</flux:heading>
                        <flux:text class="text-sm">Wer neue Dokumente einstellen darf.</flux:text>
                    </div>
                    <flux:button type="button" size="sm" icon="users" wire:click="openUserManager('uploader')">
                        Benutzer verwalten
                    </flux:button>
                </div>
                <flux:input
                    wire:model.live.debounce.300ms="permissionUpload"
                    label="Permission"
                    description="Standard: {{ \Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::defaultUpload() }}"
                    required
                />
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <flux:text class="text-sm font-medium">Rollen mit dieser Permission:</flux:text>
                    @forelse($this->uploadRoles as $roleName)
                        <flux:badge>{{ $roleName }}</flux:badge>
                    @empty
                        <flux:text class="text-sm text-zinc-500">Keine Rolle gefunden (Permission ggf. noch nicht synchronisiert oder ohne Rollenzuweisung).</flux:text>
                    @endforelse
                </div>
            </div>

            <div class="space-y-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="md">Zur Kenntnisnahme</flux:heading>
                        <flux:text class="text-sm">Wer beim Hochladen/Aktualisieren „Zur Kenntnisnahme“ aktivieren darf (Standard-Rolle enthält auch Upload).</flux:text>
                    </div>
                    <flux:button type="button" size="sm" icon="users" wire:click="openUserManager('kenntnisnahme')">
                        Benutzer verwalten
                    </flux:button>
                </div>
                <flux:input
                    wire:model.live.debounce.300ms="permissionKenntnisnahme"
                    label="Permission"
                    description="Standard: {{ \Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::defaultKenntnisnahme() }}"
                    required
                />
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <flux:text class="text-sm font-medium">Rollen mit dieser Permission:</flux:text>
                    @forelse($this->kenntnisnahmeRoles as $roleName)
                        <flux:badge>{{ $roleName }}</flux:badge>
                    @empty
                        <flux:text class="text-sm text-zinc-500">Keine Rolle gefunden (Permission ggf. noch nicht synchronisiert oder ohne Rollenzuweisung).</flux:text>
                    @endforelse
                </div>
            </div>

            <div class="space-y-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="md">GVP-Auswahl</flux:heading>
                        <flux:text class="text-sm">Wer beim Upload die GVP manuell wählen darf (sonst gilt die GVP des Verantwortlichen; Standard-Rolle enthält auch Upload).</flux:text>
                    </div>
                    <flux:button type="button" size="sm" icon="users" wire:click="openUserManager('gvp_chooser')">
                        Benutzer verwalten
                    </flux:button>
                </div>
                <flux:input
                    wire:model.live.debounce.300ms="permissionChooseGvp"
                    label="Permission"
                    description="Standard: {{ \Hwkdo\IntranetAppDokumente\Support\DocumentPermissions::defaultChooseGvp() }}"
                    required
                />
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <flux:text class="text-sm font-medium">Rollen mit dieser Permission:</flux:text>
                    @forelse($this->chooseGvpRoles as $roleName)
                        <flux:badge>{{ $roleName }}</flux:badge>
                    @empty
                        <flux:text class="text-sm text-zinc-500">Keine Rolle gefunden (Permission ggf. noch nicht synchronisiert oder ohne Rollenzuweisung).</flux:text>
                    @endforelse
                </div>
            </div>

            <flux:button type="submit" variant="primary">Berechtigungen speichern</flux:button>
        </form>
    </flux:card>

    <flux:modal wire:model="showUserModal" class="max-w-2xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Benutzer verwalten</flux:heading>
                <flux:text class="mt-1">
                    Rolle <strong>{{ $managingRoleName }}</strong>
                    @if($managingRoleKey !== '')
                        ({{ $this->managingRoleLabel() }})
                    @endif
                    — ausgewählte Benutzer erscheinen oben in der Liste.
                </flux:text>
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
