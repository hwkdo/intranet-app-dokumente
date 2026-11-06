@php
    $requiresModel = $requiresModel ?? 'uploadRequiresAcknowledgment';
    $methodModel = $methodModel ?? 'uploadAcknowledgmentMethod';
    $urlModel = $urlModel ?? 'uploadFormwerkFormUrl';
    $urlModeModel = $urlModeModel ?? 'uploadFormwerkUrlMode';
    $customModel = $customModel ?? 'uploadFormwerkUseCustomPayload';
    $rowsModel = $rowsModel ?? 'uploadFormwerkPayload';
    $onboardingItModel = $onboardingItModel ?? 'uploadIsOnboardingIt';
    $onboardingPersoModel = $onboardingPersoModel ?? 'uploadIsOnboardingPerso';
    $reAcknowledgmentModel = $reAcknowledgmentModel ?? null;
    $target = $target ?? 'upload';
    $showOnboarding = $showOnboarding ?? true;
    $showAcknowledgment = $showAcknowledgment ?? true;
    $standardFormUrl = $this->formwerkStandardFormUrl;
@endphp

@if($showOnboarding)
    <div class="grid gap-4 sm:grid-cols-2">
        <flux:switch
           align="left"
           wire:model.live="{{ $onboardingItModel }}"
            label="Onboarding IT"
            description="Dokument gehört zum IT-Onboarding neuer Mitarbeitender."
        />
        <flux:switch
           align="left"
           wire:model.live="{{ $onboardingPersoModel }}"
            label="Onboarding Personal"
            description="Dokument gehört zum Personal-Onboarding neuer Mitarbeitender."
        />
    </div>
@endif

@if($showAcknowledgment)
    <div @class([
        'overflow-hidden rounded-lg border transition-colors',
        'border-zinc-200 dark:border-zinc-700' => ! $this->{$requiresModel},
        'border-accent/50' => $this->{$requiresModel},
    ])>
        <div class="p-4">
            <flux:switch
               align="left"
               wire:model.live="{{ $requiresModel }}"
                label="Zur Kenntnisnahme"
                description="Alle Benutzer mit Zugriff auf die App müssen das Dokument bestätigen."
            />
        </div>

        @if($this->{$requiresModel})
            <div class="space-y-6 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:radio.group wire:model.live="{{ $methodModel }}" label="Art der Kenntnisnahme" variant="cards" class="max-sm:flex-col">
                    <flux:radio value="password" icon="key" label="Passworteingabe" description="Bestätigung nach dem Download per Passworteingabe." />
                    <flux:radio value="formwerk" icon="pencil-square" label="Unterschrift (Formwerk)" description="Bestätigung erst nach Webhook mit hinterlegtem PDF." />
                </flux:radio.group>

                @if($this->{$methodModel} === 'formwerk')
                    <div class="space-y-6 border-l-2 border-zinc-300 pl-4 dark:border-zinc-600">
                        <div class="space-y-3">
                            <flux:radio.group wire:model.live="{{ $urlModeModel }}" label="Formular" variant="cards" class="max-sm:flex-col">
                                <flux:radio value="standard" label="Standard-Formular" description="Formular aus den Dokumente-Einstellungen." />
                                <flux:radio value="custom" label="Abweichende Formular-URL" description="Eigenes Formwerk-Formular nur für dieses Dokument." />
                            </flux:radio.group>

                            @if($this->{$urlModeModel} === 'custom')
                                <flux:input
                                    wire:model="{{ $urlModel }}"
                                    type="url"
                                    label="Formular-URL"
                                    placeholder="https://my.formwerk.app/…"
                                    required
                                />
                            @elseif($standardFormUrl)
                                <flux:link
                                    :href="$standardFormUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-sm"
                                >
                                    Leeres Standard-Formular öffnen
                                    <flux:icon name="arrow-top-right-on-square" variant="micro" />
                                </flux:link>
                            @else
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <flux:callout.text>
                                        In den Dokumente-Einstellungen ist keine Standard-Formular-URL hinterlegt. Bitte eine abweichende URL angeben oder die Einstellung ergänzen.
                                    </flux:callout.text>
                                </flux:callout>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <flux:switch
                               align="left"
                               wire:model.live="{{ $customModel }}"
                                label="Abweichender Datensatz"
                                description="Aus = Standard-Datensatz. Werte sind fester Text oder Platzhalter wie {vorname}."
                            />
                            @if($this->{$customModel})
                                <div class="space-y-2">
                                    @foreach($this->{$rowsModel} as $index => $row)
                                        <div class="grid grid-cols-[10rem_1fr_auto] items-end gap-2" wire:key="{{ $rowsModel }}-{{ $index }}">
                                            <flux:input wire:model="{{ $rowsModel }}.{{ $index }}.key" :label="$loop->first ? 'Feld' : null" placeholder="abteilung" />
                                            <flux:input wire:model="{{ $rowsModel }}.{{ $index }}.value" :label="$loop->first ? 'Wert' : null" placeholder="{abteilung}" />
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                icon="x-mark"
                                                aria-label="Zeile entfernen"
                                                wire:click="removeFormwerkPayloadRow('{{ $target }}', {{ $index }})"
                                            />
                                        </div>
                                    @endforeach
                                    <flux:button type="button" variant="filled" size="sm" icon="plus" wire:click="addFormwerkPayloadRow('{{ $target }}')">
                                        Zeile hinzufügen
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($reAcknowledgmentModel)
                    <flux:switch
                       align="left"
                       wire:model="{{ $reAcknowledgmentModel }}"
                        label="Erneute Kenntnisnahme aller Benutzer erforderlich"
                        description="Wenn deaktiviert, gilt die neue Version ohne erneute Bestätigung als zur Kenntnis genommen."
                    />
                @endif
            </div>
        @endif
    </div>
@endif
